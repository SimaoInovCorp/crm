<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutomationRule\StoreAutomationRuleRequest;
use App\Http\Requests\AutomationRule\UpdateAutomationRuleRequest;
use App\Http\Resources\AutomationRuleResource;
use App\Models\AutomationRule;
use App\Services\AutomationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function __construct(private AutomationRuleService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AutomationRuleResource::collection($this->service->index());
    }

    public function store(StoreAutomationRuleRequest $request): JsonResponse
    {
        $rule = $this->service->create($request->validated());
        return (new AutomationRuleResource($rule))->response()->setStatusCode(201);
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $automationRule): AutomationRuleResource
    {
        if ($automationRule->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        return new AutomationRuleResource($this->service->update($automationRule, $request->validated()));
    }

    public function destroy(AutomationRule $automationRule): JsonResponse
    {
        if ($automationRule->tenant_id !== app('current.tenant')->id) {
            abort(404);
        }
        $this->service->delete($automationRule);
        return response()->json(null, 204);
    }
}
