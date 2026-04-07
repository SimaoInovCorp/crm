<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'vat'          => $this->vat,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'status'       => $this->status,
            'people_count' => $this->whenCounted('people'),
            'deals_count'  => $this->whenCounted('deals'),
            'people'       => PersonResource::collection($this->whenLoaded('people')),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
