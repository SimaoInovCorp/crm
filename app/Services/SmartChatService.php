<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class SmartChatService
{
    /**
     * System prompt — explains schema and strict tenant scoping to the model.
     */
    private function systemPrompt(int $tenantId): string
    {
        return <<<PROMPT
You are a CRM data assistant. Answer questions about CRM data concisely and directly.
You MUST always scope every query to tenant_id = {$tenantId}.

Available tables and key columns:
- entities(id, tenant_id, name, vat, email, phone, status)
- people(id, tenant_id, entity_id, name, email, phone, position)
- deals(id, tenant_id, entity_id, person_id, title, value, stage, probability, expected_close_date, owner_id)
- products(id, tenant_id, name, description)
- deal_products(deal_id, product_id, quantity, price)
- activity_logs(id, tenant_id, loggable_type, loggable_id, type, description, user_id, created_at)
- calendar_events(id, tenant_id, title, start_at, end_at, location, owner_id)

Stages: lead, contact, proposal, negotiation, won, lost

When the user asks a question, respond in one of two ways:
1. If you can answer with a SQL query, respond ONLY with JSON:
   {"type":"query","sql":"SELECT ... WHERE tenant_id = {$tenantId} ...","explanation":"<direct answer assuming results exist, e.g. 'Yes, Dundler Mifflin exists.' or 'There are 3 deals in proposal stage.'>"}
   - SQL MUST include WHERE tenant_id = {$tenantId} or equivalent JOIN scoping.
   - The explanation MUST be the direct answer to the user's question (not a SQL description).
2. If conversational or cannot be answered with SQL, respond ONLY with JSON:
   {"type":"answer","content":"<1-2 sentence answer, no markdown>"}

STYLE RULES (strictly enforced):
- Be concise. No markdown. No filler phrases. Max 2 sentences.
- Never describe what the SQL does. Always state the answer directly.
- NEVER generate INSERT, UPDATE, DELETE, DROP, TRUNCATE, or ALTER SQL.
- NEVER remove the tenant_id = {$tenantId} filter.
- NEVER access tables outside the list above.
- Only SELECT statements are allowed.
- Database is MySQL 8. Use MySQL syntax only. Use LIKE (not ILIKE) for case-insensitive text search.
PROMPT;
    }

    /**
     * Build a non-streaming response.
     */
    public function chat(Tenant $tenant, array $messages): array
    {
        $formattedMessages = $this->formatMessages($tenant, $messages);

        $response = OpenAI::chat()->create([
            'model'    => config('openai.model', 'gpt-4o-mini'),
            'messages' => $formattedMessages,
        ]);

        $content = $response->choices[0]->message->content ?? '';

        return $this->processResponse($tenant->id, $content);
    }

    /**
     * Generator for streaming — yields partial content chunks.
     */
    public function chatStream(Tenant $tenant, array $messages): \Generator
    {
        $formattedMessages = $this->formatMessages($tenant, $messages);

        $stream = OpenAI::chat()->createStreamed([
            'model'    => config('openai.model', 'gpt-4o-mini'),
            'messages' => $formattedMessages,
        ]);

        foreach ($stream as $response) {
            $delta = $response->choices[0]->delta->content ?? '';
            if ($delta !== '' && $delta !== null) {
                yield $delta;
            }
        }
    }

    /**
     * Execute a safe SELECT query scoped to tenant.
     */
    public function executeQuery(int $tenantId, string $sql): array
    {
        // Safety check: only allow SELECT
        $trimmed = ltrim($sql);
        if (!preg_match('/^SELECT\s/i', $trimmed)) {
            return ['error' => 'Only SELECT queries are allowed.'];
        }

        // Ensure tenant_id scoping is present
        if (!str_contains(strtolower($sql), 'tenant_id')) {
            return ['error' => 'Query must include tenant_id scoping.'];
        }

        // MySQL does not support ILIKE — replace with LIKE (MySQL LIKE is case-insensitive by default for utf8 collations)
        $sql = preg_replace('/\bILIKE\b/i', 'LIKE', $sql);

        try {
            $results = DB::select($sql);
            return ['data' => $results];
        } catch (\Throwable $e) {
            return ['error' => 'Query failed: '.$e->getMessage()];
        }
    }

    private function formatMessages(Tenant $tenant, array $messages): array
    {
        $formatted = [
            ['role' => 'system', 'content' => $this->systemPrompt($tenant->id)],
        ];

        foreach ($messages as $message) {
            $formatted[] = [
                'role'    => in_array($message['role'] ?? '', ['user', 'assistant']) ? $message['role'] : 'user',
                'content' => strip_tags((string) ($message['content'] ?? '')),
            ];
        }

        return $formatted;
    }

    public function processStreamedResponse(int $tenantId, string $content): array
    {
        return $this->processResponse($tenantId, $content);
    }

    /**
     * Execute a predefined quick query by key, scoped to the given tenant.
     * Bypasses AI entirely — safe, tenant-scoped SQL only.
     */
    public function quickQuery(int $tenantId, string $key): array
    {
        $queries = [
            'proposal_count' => [
                'sql'         => "SELECT COUNT(*) as count FROM deals WHERE tenant_id = {$tenantId} AND stage = 'proposal'",
                'explanation' => 'Deals currently in the Proposal stage.',
            ],
            'top5_entities' => [
                'sql'         => "SELECT e.name, SUM(CAST(d.value AS DECIMAL(15,2))) as total_value FROM entities e INNER JOIN deals d ON d.entity_id = e.id WHERE e.tenant_id = {$tenantId} GROUP BY e.id, e.name ORDER BY total_value DESC LIMIT 5",
                'explanation' => 'Top 5 entities by total deal value.',
            ],
            'closing_this_month' => [
                'sql'         => "SELECT title, CAST(value AS DECIMAL(15,2)) as value, stage, expected_close_date FROM deals WHERE tenant_id = {$tenantId} AND MONTH(expected_close_date) = MONTH(CURDATE()) AND YEAR(expected_close_date) = YEAR(CURDATE()) ORDER BY expected_close_date ASC",
                'explanation' => 'Deals expected to close this month.',
            ],
            'deals_above_10k' => [
                'sql'         => "SELECT title, CAST(value AS DECIMAL(15,2)) as value, stage FROM deals WHERE tenant_id = {$tenantId} AND CAST(value AS DECIMAL(15,2)) > 10000 ORDER BY CAST(value AS DECIMAL(15,2)) DESC",
                'explanation' => 'All deals with a value above €10,000.',
            ],
            'active_entities' => [
                'sql'         => "SELECT COUNT(*) as count FROM entities WHERE tenant_id = {$tenantId} AND status = 'active'",
                'explanation' => 'Number of entities with Active status.',
            ],
        ];

        if (!isset($queries[$key])) {
            return ['type' => 'answer', 'content' => 'Unknown quick query key.'];
        }

        $q       = $queries[$key];
        $results = $this->executeQuery($tenantId, $q['sql']);

        return [
            'type'        => 'query',
            'sql'         => $q['sql'],
            'explanation' => $q['explanation'],
            'results'     => $results,
        ];
    }

    private function processResponse(int $tenantId, string $content): array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $content);
        $decoded = json_decode(trim($clean ?? $content), true);

        if (!is_array($decoded)) {
            return ['type' => 'answer', 'content' => $content];
        }

        if (($decoded['type'] ?? '') === 'query' && isset($decoded['sql'])) {
            // Double-check tenant isolation before executing
            if (!str_contains(strtolower($decoded['sql']), (string) $tenantId)) {
                $decoded['sql'] .= " AND tenant_id = $tenantId";
            }
            $queryResult = $this->executeQuery($tenantId, $decoded['sql']);
            $decoded['results'] = $queryResult;

            // Override explanation based on actual result data so the user sees a real answer
            if (isset($queryResult['error'])) {
                $decoded['explanation'] = 'Query error: ' . $queryResult['error'];
            } elseif (empty($queryResult['data'])) {
                $decoded['explanation'] = 'No matching records found.';
            } else {
                $rows = $queryResult['data'];
                $firstRow = (array) $rows[0];
                // If it's a count(*) query, replace AI's estimate with the real count
                if (count($firstRow) === 1 && array_key_exists('count', $firstRow)) {
                    $n = (int) $firstRow['count'];
                    $decoded['explanation'] = "There are {$n} matching record" . ($n === 1 ? '' : 's') . ".";
                }
                // Otherwise keep the AI's explanation (now written as a direct answer)
            }
        }

        return $decoded;
    }
}
