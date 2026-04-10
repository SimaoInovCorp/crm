<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deal\UpdateDealStageRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\DealService;

class DealStageController extends Controller
{
    public function __construct(private DealService $dealService) {}

    public function __invoke(UpdateDealStageRequest $request, Deal $deal): DealResource
    {
        $this->authorize('update', $deal);
        $deal = $this->dealService->updateStage($deal, $request->validated('stage'));
        return new DealResource($deal);
    }
}
