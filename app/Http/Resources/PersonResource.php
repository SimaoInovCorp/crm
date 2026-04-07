<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'entity_id'  => $this->entity_id,
            'entity'     => new EntityResource($this->whenLoaded('entity')),
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'position'   => $this->position,
            'notes'      => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
