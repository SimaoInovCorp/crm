<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadForm\StoreLeadFormRequest;
use App\Http\Requests\LeadForm\UpdateLeadFormRequest;
use App\Http\Resources\LeadFormResource;
use App\Http\Resources\LeadFormSubmissionResource;
use App\Models\LeadForm;
use App\Services\LeadFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadFormController extends Controller
{
    public function __construct(private LeadFormService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return LeadFormResource::collection($this->service->index());
    }

    public function store(StoreLeadFormRequest $request): JsonResponse
    {
        $form = $this->service->create(array_merge(
            $request->validated(),
            ['tenant_id' => app('current.tenant')->id]
        ));
        return (new LeadFormResource($form))->response()->setStatusCode(201);
    }

    public function show(LeadForm $leadForm): LeadFormResource
    {
        if ($leadForm->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new LeadFormResource($leadForm);
    }

    public function update(UpdateLeadFormRequest $request, LeadForm $leadForm): LeadFormResource
    {
        if ($leadForm->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new LeadFormResource($this->service->update($leadForm, $request->validated()));
    }

    public function destroy(LeadForm $leadForm): JsonResponse
    {
        if ($leadForm->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $this->service->delete($leadForm);
        return response()->json(null, 204);
    }

    public function submissions(LeadForm $leadForm): AnonymousResourceCollection
    {
        if ($leadForm->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return LeadFormSubmissionResource::collection($this->service->submissions($leadForm));
    }
}
