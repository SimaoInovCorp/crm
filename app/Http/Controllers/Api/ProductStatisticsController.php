<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductStatisticsController extends Controller
{
    public function __construct(private readonly ProductStatisticsService $service) {}

    /**
     * GET /api/products/statistics
     * Aggregated stats for all products in the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'stage', 'owner_id']);
        $stats = $this->service->statistics($filters);

        return response()->json(['data' => $stats->values()]);
    }

    /**
     * GET /api/products/{product}/drill-down
     * List all deals that contain a given product.
     */
    public function drillDown(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $filters = $request->only(['date_from', 'date_to', 'stage', 'owner_id']);
        $deals = $this->service->drillDown($product, $filters);

        return response()->json([
            'data' => $deals->map(fn ($deal) => [
                'id'                  => $deal->id,
                'title'               => $deal->title,
                'stage'               => $deal->stage,
                'value'               => (float) $deal->value,
                'expected_close_date' => $deal->expected_close_date,
                'quantity'            => (int) $deal->quantity,
                'unit_price'          => (float) $deal->price,
                'line_total'          => round($deal->quantity * $deal->price, 2),
                'entity'              => $deal->entity ? ['id' => $deal->entity->id, 'name' => $deal->entity->name] : null,
                'owner'               => $deal->owner  ? ['id' => $deal->owner->id,  'name' => $deal->owner->name]  : null,
            ]),
        ]);
    }
}
