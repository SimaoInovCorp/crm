<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * List webhooks for the active tenant (paginated).
     */
    public function index(): LengthAwarePaginator
    {
        return Webhook::orderBy('created_at', 'desc')
            ->paginate(request()->integer('per_page', 25));
    }

    /**
     * Create a new webhook.
     */
    public function create(array $data): Webhook
    {
        return Webhook::create($data);
    }

    /**
     * Update an existing webhook.
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update($data);
        return $webhook->fresh();
    }

    /**
     * Delete a webhook.
     */
    public function delete(Webhook $webhook): void
    {
        $webhook->delete();
    }

    /**
     * Dispatch an event to all active webhooks subscribed to it.
     * Sends a signed HTTP POST to each webhook URL.
     *
     * Events: deal.won | lead.created | automation.fired
     */
    public function dispatch(string $event, array $payload): void
    {
        $tenantId = app('current.tenant')?->id;

        if (! $tenantId) {
            return;
        }

        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $wh) => in_array($event, $wh->events ?? []));

        $body = json_encode([
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'payload'   => $payload,
        ]);

        foreach ($webhooks as $webhook) {
            try {
                $headers = [
                    'Content-Type'      => 'application/json',
                    'X-CRM-Event'       => $event,
                    'X-CRM-Timestamp'   => now()->timestamp,
                ];

                if ($webhook->secret) {
                    $headers['X-CRM-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);
                }

                Http::withHeaders($headers)
                    ->timeout(5)
                    ->withBody($body, 'application/json')
                    ->post($webhook->url);
            } catch (\Throwable $e) {
                Log::warning("Webhook dispatch failed for [{$webhook->url}]: " . $e->getMessage());
            }
        }
    }
}
