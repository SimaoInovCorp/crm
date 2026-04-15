<?php

use App\Models\Deal;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// ─── Helper ───────────────────────────────────────────────────────────────────

function webhookTenant(string $slug = 'wh-test'): array
{
    $user   = User::factory()->create();
    $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'owner_id' => $user->id]);
    TenantUser::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner']);
    return [$user, $tenant];
}

// ─── CRUD Tests ───────────────────────────────────────────────────────────────

it('can create a webhook', function () {
    [$user, $tenant] = webhookTenant('wh-create');

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->postJson('/api/webhooks', [
            'url'       => 'https://example.com/hook',
            'events'    => ['deal.won'],
            'is_active' => true,
        ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.url', 'https://example.com/hook')
             ->assertJsonPath('data.events.0', 'deal.won');
});

it('rejects invalid event names', function () {
    [$user, $tenant] = webhookTenant('wh-invalid');

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->postJson('/api/webhooks', [
            'url'    => 'https://example.com/hook',
            'events' => ['invalid.event'],
        ])
        ->assertStatus(422);
});

it('can list webhooks with pagination', function () {
    [$user, $tenant] = webhookTenant('wh-list');
    app()->instance('current.tenant', $tenant);

    Webhook::create(['tenant_id' => $tenant->id, 'url' => 'https://a.com/hook', 'events' => ['deal.won']]);
    Webhook::create(['tenant_id' => $tenant->id, 'url' => 'https://b.com/hook', 'events' => ['lead.created']]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->getJson('/api/webhooks');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('can update a webhook', function () {
    [$user, $tenant] = webhookTenant('wh-update');
    app()->instance('current.tenant', $tenant);

    $webhook = Webhook::create(['tenant_id' => $tenant->id, 'url' => 'https://old.com', 'events' => ['deal.won']]);

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->putJson("/api/webhooks/{$webhook->id}", ['url' => 'https://new.com'])
        ->assertOk()
        ->assertJsonPath('data.url', 'https://new.com');
});

it('can delete a webhook', function () {
    [$user, $tenant] = webhookTenant('wh-delete');
    app()->instance('current.tenant', $tenant);

    $webhook = Webhook::create(['tenant_id' => $tenant->id, 'url' => 'https://del.com', 'events' => ['deal.won']]);

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->deleteJson("/api/webhooks/{$webhook->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
});

it('cannot access another tenants webhook', function () {
    [$user1, $tenant1] = webhookTenant('wh-iso1');
    [$user2, $tenant2] = webhookTenant('wh-iso2');

    app()->instance('current.tenant', $tenant2);
    $webhook = Webhook::create(['tenant_id' => $tenant2->id, 'url' => 'https://secret.com', 'events' => ['deal.won']]);

    $this->actingAs($user1)
        ->withHeaders(['X-Tenant' => $tenant1->slug])
        ->deleteJson("/api/webhooks/{$webhook->id}")
        ->assertStatus(404);
});
