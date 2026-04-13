<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deal\StartFollowUpRequest;
use App\Http\Resources\FollowUpAutomationResource;
use App\Models\Deal;
use App\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function __construct(private FollowUpService $service) {}

    public function show(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);
        $followUp = $this->service->forDeal($deal);

        if (! $followUp) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => new FollowUpAutomationResource($followUp)]);
    }

    public function start(StartFollowUpRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);
        $followUp = $this->service->start($deal, $request->validated('email_template_id'));
        return (new FollowUpAutomationResource($followUp->load('emailTemplate')))->response()->setStatusCode(201);
    }

    public function cancel(Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);
        $this->service->cancel($deal);
        return response()->json(['message' => 'Follow-up cancelled.']);
    }

    public function sendNow(Request $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $templateId = $request->input('email_template_id') ? (int) $request->input('email_template_id') : null;
        $customBody = $request->input('body');

        // Without an active follow-up, a template must be explicitly chosen
        if (! $templateId) {
            $followUp = $this->service->forDeal($deal);
            if (! $followUp || $followUp->status !== 'active') {
                return response()->json(['message' => 'Please select an email template to send.'], 422);
            }
        }

        $sent = $this->service->sendNow($deal, $templateId, $customBody);

        if (! $sent) {
            return response()->json(['message' => 'Could not send: no template or recipient email configured.'], 422);
        }

        $followUp = $this->service->forDeal($deal);
        return response()->json([
            'message' => 'Email sent successfully.',
            'data'    => $followUp ? new FollowUpAutomationResource($followUp->load('emailTemplate')) : null,
        ]);
    }
}
