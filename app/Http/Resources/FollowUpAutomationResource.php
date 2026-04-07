<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpAutomationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'emails_sent'     => $this->emails_sent,
            'next_send_at'    => $this->next_send_at?->toIso8601String(),
            'last_sent_at'    => $this->last_sent_at?->toIso8601String(),
            'email_template'  => $this->whenLoaded('emailTemplate', fn () => [
                'id'   => $this->emailTemplate->id,
                'name' => $this->emailTemplate->name,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
