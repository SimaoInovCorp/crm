<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadForm\SubmitLeadFormRequest;
use App\Http\Resources\LeadFormSubmissionResource;
use App\Services\LeadFormService;
use Illuminate\Http\JsonResponse;

class PublicLeadFormController extends Controller
{
    public function __construct(private LeadFormService $service) {}

    public function submit(string $token, SubmitLeadFormRequest $request): JsonResponse
    {
        $form = $this->service->findByToken($token);

        $submission = $this->service->submit(
            $form,
            $request->sanitized(),
            $request->ip(),
            $request->header('Origin')
        );

        return (new LeadFormSubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }
}
