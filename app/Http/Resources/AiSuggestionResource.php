<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'deal_id'        => $this->deal_id,
            'deal'           => $this->whenLoaded('deal', fn () => [
                'id'    => $this->deal->id,
                'title' => $this->deal->title,
                'stage' => $this->deal->stage,
                'value' => $this->deal->value,
            ]),
            'type'           => $this->type,
            'rationale'      => $this->rationale,
            'status'         => $this->status,
            'postpone_until' => $this->postpone_until?->toIso8601String(),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
