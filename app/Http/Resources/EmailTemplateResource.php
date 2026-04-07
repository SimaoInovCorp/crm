<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'subject'    => $this->subject,
            'body'       => $this->body,
            'type'       => $this->type,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
