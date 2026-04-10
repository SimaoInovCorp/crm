<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Deal;
use App\Services\ActivityLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealTimelineController extends Controller
{
    public function __construct(private ActivityLogService $service) {}

    public function index(Deal $deal): AnonymousResourceCollection
    {
        $this->authorize('view', $deal);

        $logs = $this->service->timeline($deal);

        return ActivityLogResource::collection($logs);
    }
}
