<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Deal;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealTimelineController extends Controller
{
    public function __construct(private ActivityLogService $service) {}

    public function index(Request $request, Deal $deal): AnonymousResourceCollection
    {
        $this->authorize('view', $deal);

        $perPage = (int) $request->query('per_page', 5);
        $logs = $this->service->timeline($deal, $perPage);

        return ActivityLogResource::collection($logs);
    }

    public function export(Deal $deal): StreamedResponse
    {
        $this->authorize('view', $deal);

        return $this->service->exportTimelineCsv($deal);
    }
}
