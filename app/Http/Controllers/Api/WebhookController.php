<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\StoreWebhookRequest;
use App\Http\Requests\Webhook\UpdateWebhookRequest;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookController extends Controller
{
    public function __construct(private WebhookService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return WebhookResource::collection($this->service->index());
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = $this->service->create(array_merge(
            $request->validated(),
            ['tenant_id' => app('current.tenant')->id]
        ));
        return (new WebhookResource($webhook))->response()->setStatusCode(201);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): WebhookResource
    {
        if ($webhook->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new WebhookResource($this->service->update($webhook, $request->validated()));
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        if ($webhook->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $this->service->delete($webhook);
        return response()->json(null, 204);
    }
}
