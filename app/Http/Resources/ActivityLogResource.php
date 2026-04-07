<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'description' => $this->description,
            'metadata'    => $this->metadata,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'loggable_type' => $this->loggable_type,
            'loggable_id'   => $this->loggable_id,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
