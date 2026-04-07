<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'value'                => $this->value,
            'stage'                => $this->stage,
            'probability'          => $this->probability,
            'expected_close_date'  => $this->expected_close_date?->toDateString(),
            'notes'                => $this->notes,
            'entity'               => $this->whenLoaded('entity', fn () => [
                'id'   => $this->entity->id,
                'name' => $this->entity->name,
            ]),
            'person'               => $this->whenLoaded('person', fn () => $this->person ? [
                'id'   => $this->person->id,
                'name' => $this->person->name,
            ] : null),
            'owner'                => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'products'             => $this->whenLoaded('dealProducts', fn () =>
                $this->dealProducts->map(fn ($dp) => [
                    'id'         => $dp->product_id,
                    'name'       => $dp->product?->name,
                    'quantity'   => $dp->quantity,
                    'unit_price' => $dp->price,
                ])
            ),
            'created_at'           => $this->created_at->toIso8601String(),
            'updated_at'           => $this->updated_at->toIso8601String(),
        ];
    }
}
