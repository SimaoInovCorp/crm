<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Entity;
use App\Models\LeadForm;
use App\Models\LeadFormSubmission;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadFormService
{
    public function __construct(private WebhookService $webhookService) {}
    public function index(): LengthAwarePaginator
    {
        return LeadForm::withCount('submissions')->orderBy('name')->paginate(request()->integer('per_page', 25));
    }

    /**
     * Find an active lead form by its public embed token (bypasses tenant scope).
     */
    public function findByToken(string $token): LeadForm
    {
        return LeadForm::withoutGlobalScopes()
            ->where('embed_token', $token)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function create(array $data): LeadForm
    {
        return LeadForm::create($data);
    }

    public function update(LeadForm $form, array $data): LeadForm
    {
        $form->update($data);
        return $form->fresh();
    }

    public function delete(LeadForm $form): void
    {
        $form->delete();
    }

    public function submissions(LeadForm $form)
    {
        return $form->submissions()->latest()->paginate(20);
    }

    /**
     * Process a public form submission: store raw data and auto-create Entity + Deal.
     */
    public function submit(LeadForm $form, array $data, string $ip = null, string $origin = null): LeadFormSubmission
    {
        $submission = LeadFormSubmission::create([
            'lead_form_id' => $form->id,
            'tenant_id'    => $form->tenant_id,
            'data'         => $data,
            'ip'           => $ip,
            'origin'       => $origin,
            'processed'    => false,
        ]);

        // Auto-create Entity + Deal from submission data
        $name  = trim(($data['name'] ?? '') . ' ' . ($data['company'] ?? ''));
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        // Temporarily bind the tenant so HasTenant global scope works
        $tenant = $form->tenant;
        app()->instance('current.tenant', $tenant);

        $entity = Entity::create([
            'tenant_id' => $form->tenant_id,
            'name'      => $name ?: 'Lead from ' . $form->name,
            'email'     => $email,
            'phone'     => $phone,
        ]);

        $deal = Deal::create([
            'tenant_id' => $form->tenant_id,
            'entity_id' => $entity->id,
            'owner_id'  => $tenant->owner_id,
            'title'     => 'Lead: ' . ($name ?: $form->name),
            'stage'     => 'new',
            'value'     => $data['budget'] ?? 0,
            'notes'     => $data['message'] ?? null,
        ]);

        $submission->update(['processed' => true, 'deal_id' => $deal->id]);

        $this->webhookService->dispatch('lead.created', [
            'lead_form'   => $form->name,
            'entity_name' => $entity->name,
            'email'       => $email,
            'deal_id'     => $deal->id,
        ]);

        return $submission->fresh();
    }
}
