<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id'   => $this->resource['product_id'],
            'product_name' => $this->resource['product_name'],
            'frequency'    => $this->resource['frequency'],
            'total_value'  => $this->resource['total_value'],
        ];
    }
}
