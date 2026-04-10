<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplate\StoreEmailTemplateRequest;
use App\Http\Requests\EmailTemplate\UpdateEmailTemplateRequest;
use App\Http\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use App\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailTemplateController extends Controller
{
    public function __construct(private FollowUpService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return EmailTemplateResource::collection($this->service->templates());
    }

    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $template = $this->service->createTemplate($request->validated());
        return (new EmailTemplateResource($template))->response()->setStatusCode(201);
    }

    public function show(EmailTemplate $emailTemplate): EmailTemplateResource
    {
        if ($emailTemplate->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new EmailTemplateResource($emailTemplate);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): EmailTemplateResource
    {
        if ($emailTemplate->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new EmailTemplateResource($this->service->updateTemplate($emailTemplate, $request->validated()));
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        if ($emailTemplate->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $this->service->deleteTemplate($emailTemplate);
        return response()->json(null, 204);
    }
}
