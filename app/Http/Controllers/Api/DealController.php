<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deal\StoreDealRequest;
use App\Http\Requests\Deal\UpdateDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    public function __construct(private DealService $dealService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Deal::class);
        $deals = $this->dealService->index($request->only(['search', 'stage', 'owner_id', 'entity_id', 'person_id']));
        return DealResource::collection($deals);
    }

    public function store(StoreDealRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Deal::class);
        $data = $request->validated();
        if (empty($data['owner_id'])) {
            $data['owner_id'] = $request->user()->id;
        }
        $deal = $this->dealService->create($data);
        return (new DealResource($deal))->response()->setStatusCode(201);
    }

    public function show(Deal $deal): DealResource
    {
        $this->authorize('view', $deal);
        $deal = $this->dealService->show($deal);
        return new DealResource($deal);
    }

    public function update(UpdateDealRequest $request, Deal $deal): DealResource
    {
        $this->authorize('update', $deal);
        $deal = $this->dealService->update($deal, $request->validated());
        return new DealResource($deal);
    }

    public function destroy(Deal $deal): JsonResponse
    {
        $this->authorize('delete', $deal);
        $this->dealService->delete($deal);
        return response()->json(null, 204);
    }
}
