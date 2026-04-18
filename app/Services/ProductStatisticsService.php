<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealProduct;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductStatisticsService
{
    /**
     * Aggregate product statistics for the current tenant.
     *
     * @param  array{date_from?: string, date_to?: string, stage?: string, owner_id?: int}  $filters
     */
    public function statistics(array $filters = []): Collection
    {
        $tenant = app('current.tenant');

        // Start from Product so zero-sold products are included (LEFT JOIN).
        // Filters go inside the deals JOIN condition so unmatched deal_products
        // produce a NULL deals.id, which we then exclude from the aggregates
        // via CASE WHEN — this keeps 0-sold products in the result.
        $query = Product::query()
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('COUNT(DISTINCT CASE WHEN deals.id IS NOT NULL THEN deal_products.deal_id ELSE NULL END) as frequency'),
                DB::raw('COALESCE(SUM(CASE WHEN deals.id IS NOT NULL THEN deal_products.quantity * deal_products.price ELSE 0 END), 0) as total_value'),
            ])
            ->leftJoin('deal_products', 'deal_products.product_id', '=', 'products.id')
            ->leftJoin('deals', function ($join) use ($tenant, $filters) {
                $join->on('deals.id', '=', 'deal_products.deal_id')
                     ->where('deals.tenant_id', $tenant->id)
                     ->whereNull('deals.deleted_at');

                if (!empty($filters['date_from'])) {
                    $join->whereDate('deals.expected_close_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $join->whereDate('deals.expected_close_date', '<=', $filters['date_to']);
                }
                if (!empty($filters['stage'])) {
                    $join->where('deals.stage', $filters['stage']);
                }
                if (!empty($filters['owner_id'])) {
                    $join->where('deals.owner_id', $filters['owner_id']);
                }
            })
            ->where('products.tenant_id', $tenant->id)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_value');

        return $query->get()->map(fn ($row) => [
            'product_id'   => $row->product_id,
            'product_name' => $row->product_name,
            'frequency'    => (int) $row->frequency,
            'total_value'  => (float) $row->total_value,
        ]);
    }

    /**
     * Return all deals that contain a given product (drill-down).
     *
     * @param  array{date_from?: string, date_to?: string, stage?: string, owner_id?: int}  $filters
     */
    public function drillDown(Product $product, array $filters = []): Collection
    {
        $tenant = app('current.tenant');

        $query = Deal::withoutGlobalScopes()
            ->with(['entity:id,name', 'owner:id,name'])
            ->select('deals.*', 'deal_products.quantity', 'deal_products.price')
            ->join('deal_products', 'deal_products.deal_id', '=', 'deals.id')
            ->where('deals.tenant_id', $tenant->id)
            ->where('deal_products.product_id', $product->id)
            ->whereNull('deals.deleted_at');

        if (!empty($filters['date_from'])) {
            $query->whereDate('deals.expected_close_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('deals.expected_close_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['stage'])) {
            $query->where('deals.stage', $filters['stage']);
        }

        if (!empty($filters['owner_id'])) {
            $query->where('deals.owner_id', $filters['owner_id']);
        }

        return $query->orderByDesc('deals.created_at')->get();
    }
}
