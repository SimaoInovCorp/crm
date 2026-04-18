<?php

namespace App\Services;

use App\Models\AiSuggestion;
use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AiSalesAgentService
{
    /**
     * List suggestions for the current tenant with optional filters.
     */
    public function listSuggestions(array $filters = []): LengthAwarePaginator
    {
        $query = AiSuggestion::with('deal')->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['deal_id'])) {
            $query->where('deal_id', (int) $filters['deal_id']);
        }

        return $query->paginate(15);
    }

    /**
     * Analyze all open deals for a tenant and generate suggestions.
     * Returns counts of created/failed/total.
     */
    public function analyzeDeals(Tenant $tenant): array
    {
        $deals = Deal::with(['entity', 'person', 'activityLogs' => fn ($q) => $q->latest()->limit(5)])
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('stage', ['won', 'lost'])
            ->get();

        $created = 0;
        $failed  = 0;

        foreach ($deals as $deal) {
            $suggestion = $this->generateSuggestionForDeal($tenant, $deal);
            if ($suggestion !== null) {
                $created++;
            } else {
                $failed++;
            }
        }

        return ['created' => $created, 'failed' => $failed, 'total' => $deals->count()];
    }

    /**
     * Generate a single AI suggestion for a deal.
     */
    public function generateSuggestionForDeal(Tenant $tenant, Deal $deal): ?AiSuggestion
    {
        $dealContext = $this->buildDealContext($deal);
        $prompt = $this->buildPrompt($dealContext);

        try {
            $response = OpenAI::chat()->create([
                'model'    => config('openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user',   'content' => $prompt],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '';
            $parsed  = $this->parseResponse($content);

            if (!$parsed) {
                Log::warning('AI suggestion parse failed for deal ' . $deal->id . '. Finish reason: '
                    . ($response->choices[0]->finishReason ?? 'unknown') . '. Content: ' . $content);
                return null;
            }

            return AiSuggestion::create([
                'tenant_id' => $tenant->id,
                'deal_id'   => $deal->id,
                'type'      => $parsed['type'],
                'rationale' => $parsed['rationale'],
                'status'    => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI suggestion generation failed for deal ' . $deal->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Accept a suggestion — creates a follow-up activity log entry.
     */
    public function accept(AiSuggestion $suggestion): AiSuggestion
    {
        $suggestion->update(['status' => 'accepted']);
        return $suggestion;
    }

    /**
     * Dismiss a suggestion.
     */
    public function dismiss(AiSuggestion $suggestion): AiSuggestion
    {
        $suggestion->update(['status' => 'dismissed']);
        return $suggestion;
    }

    /**
     * Postpone a suggestion for N days (default: 3).
     */
    public function postpone(AiSuggestion $suggestion, int $days = 3): AiSuggestion
    {
        $suggestion->update([
            'status'         => 'postponed',
            'postpone_until' => Carbon::now()->addDays($days),
        ]);
        return $suggestion;
    }

    private function systemPrompt(): string
    {
        return 'You are a CRM sales coach AI. Analyze the given deal data and activity history, '
            .'then suggest ONE concrete, actionable next step the salesperson should take. '
            .'Respond ONLY with valid JSON using exactly this structure: '
            .'{"type":"<TYPE>","rationale":"<1-2 sentence explanation>"}. '
            .'The type field MUST be exactly one of these values: follow_up, discount, close_now, escalate, re_engage, schedule_call. '
            .'No markdown. No code fences. No extra fields.';
    }

    private function buildDealContext(Deal $deal): array
    {
        $daysSinceActivity = null;
        $latestActivity    = $deal->activityLogs->first();
        if ($latestActivity) {
            $daysSinceActivity = (int) $latestActivity->created_at->diffInDays(now());
        }

        return [
            'title'              => $deal->title,
            'stage'              => $deal->stage,
            'value'              => $deal->value,
            'probability'        => $deal->probability,
            'expected_close'     => $deal->expected_close_date?->format('Y-m-d'),
            'days_to_close'      => $deal->expected_close_date ? (int) now()->diffInDays($deal->expected_close_date, false) : null,
            'days_since_activity'=> $daysSinceActivity,
            'recent_activities'  => $deal->activityLogs->map(fn ($log) => [
                'type'        => $log->type,
                'description' => $log->description,
                'days_ago'    => (int) $log->created_at->diffInDays(now()),
            ])->toArray(),
        ];
    }

    private function buildPrompt(array $context): string
    {
        return 'Deal: '.$context['title']
            ."\nStage: ".$context['stage']
            ."\nValue: ".$context['value']
            ."\nProbability: ".$context['probability'].'%'
            ."\nExpected close: ".($context['expected_close'] ?? 'not set')
            ."\nDays to close: ".($context['days_to_close'] !== null ? $context['days_to_close'] : 'unknown')
            ."\nDays since last activity: ".($context['days_since_activity'] !== null ? $context['days_since_activity'] : 'no activity')
            ."\nRecent activities: ".json_encode($context['recent_activities'] ?? []);
    }

    private function parseResponse(string $content): ?array
    {
        $clean   = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $content);
        $decoded = json_decode(trim($clean ?? $content), true);

        if (!is_array($decoded)) {
            return null;
        }

        $validTypes = ['follow_up', 'discount', 'close_now', 'escalate', 're_engage', 'schedule_call'];
        $type = strtolower(trim($decoded['type'] ?? ''));
        if (!in_array($type, $validTypes, true)) {
            return null;
        }
        $decoded['type'] = $type;

        if (empty($decoded['rationale'])) {
            return null;
        }

        return $decoded;
    }
}
