<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\StoreActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function __construct(private ActivityLogService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->service->index($request->only(['loggable_type', 'loggable_id', 'type', 'user_id']));

        return ActivityLogResource::collection($logs);
    }

    public function store(StoreActivityLogRequest $request): JsonResponse
    {
        $loggableClass = $request->validated('loggable_type');
        $loggable = $loggableClass::findOrFail($request->validated('loggable_id'));

        if ($loggable->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }

        $log = $this->service->log(
            $loggable,
            $request->validated('type'),
            $request->validated('description'),
            $request->validated('metadata', [])
        );

        return (new ActivityLogResource($log->load('user')))
            ->response()
            ->setStatusCode(201);
    }
}
