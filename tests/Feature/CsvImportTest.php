<?php

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─── Helper ───────────────────────────────────────────────────────────────────

function csvTenant(string $slug): array
{
    $user   = User::factory()->create();
    $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'owner_id' => $user->id]);
    TenantUser::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner']);
    return [$user, $tenant];
}

function makeCsv(string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
    file_put_contents($path, $content);
    return new UploadedFile($path, 'import.csv', 'text/plain', null, true);
}

// ─── Entity Import ────────────────────────────────────────────────────────────

it('imports entities from csv', function () {
    [$user, $tenant] = csvTenant('csv-ent');

    $csv = makeCsv("name,email,status\nAcme Corp,acme@example.com,active\nGlobex,globex@example.com,active\n");

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->post('/api/import/entities', ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('imported', 2);

    $this->assertDatabaseHas('entities', ['name' => 'Acme Corp', 'tenant_id' => $tenant->id]);
    $this->assertDatabaseHas('entities', ['name' => 'Globex', 'tenant_id' => $tenant->id]);
});

it('rejects entity rows missing required name column', function () {
    [$user, $tenant] = csvTenant('csv-ent-err');

    $csv = makeCsv("email,status\nacme@example.com,active\n");

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->post('/api/import/entities', ['file' => $csv]);

    // The one row that fails: reports an error but still returns 422
    $response->assertStatus(422)->assertJsonPath('imported', 0);
});

// ─── People Import ────────────────────────────────────────────────────────────

it('imports people from csv', function () {
    [$user, $tenant] = csvTenant('csv-ppl');

    $csv = makeCsv("name,email,position\nJohn Doe,john@example.com,Sales\nJane Smith,jane@example.com,CTO\n");

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->post('/api/import/people', ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('imported', 2);

    $this->assertDatabaseHas('people', ['name' => 'John Doe', 'tenant_id' => $tenant->id]);
});

// ─── Deals Import ─────────────────────────────────────────────────────────────

it('imports deals from csv', function () {
    [$user, $tenant] = csvTenant('csv-deals');
    app()->instance('current.tenant', $tenant);

    // Create entity first so entity_id FK is satisfied
    $entity = \App\Models\Entity::create(['tenant_id' => $tenant->id, 'name' => 'Test Corp', 'status' => 'active']);

    $csv = makeCsv("title,value,stage,entity_id\nBig Deal,10000,lead,{$entity->id}\nSmall Deal,500,proposal,{$entity->id}\n");

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->post('/api/import/deals', ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('imported', 2);

    $this->assertDatabaseHas('deals', ['title' => 'Big Deal', 'tenant_id' => $tenant->id]);
});

// ─── Invalid Type / File Validation ──────────────────────────────────────────

it('rejects invalid import type via route constraint', function () {
    [$user, $tenant] = csvTenant('csv-type');

    $csv = makeCsv("name\nTest\n");

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->post('/api/import/widgets', ['file' => $csv])
        ->assertStatus(404);
});

it('rejects request with no file', function () {
    [$user, $tenant] = csvTenant('csv-mime');

    $this->actingAs($user)
        ->withHeaders(['X-Tenant' => $tenant->slug])
        ->postJson('/api/import/entities', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});
