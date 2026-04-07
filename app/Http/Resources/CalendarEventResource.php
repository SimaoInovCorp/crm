<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'location'       => $this->location,
            'start_at'       => $this->start_at?->toISOString(),
            'end_at'         => $this->end_at?->toISOString(),
            'all_day'        => $this->all_day,
            'eventable_type' => $this->eventable_type,
            'eventable_id'   => $this->eventable_id,
            'owner'          => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'attendees'      => $this->whenLoaded('attendees', fn () =>
                $this->attendees->map(fn ($a) => [
                    'id'           => $a->id,
                    'attendee_type'=> $a->attendee_type,
                    'attendee_id'  => $a->attendee_id,
                ])
            ),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
