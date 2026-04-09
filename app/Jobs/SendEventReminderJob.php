<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendEventReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public CalendarEvent $event) {}

    public function handle(): void
    {
        // Reload with attendees in case data changed after dispatch
        $this->event->loadMissing(['attendees.attendee', 'owner']);

        // Collect notification recipients
        $recipients = collect();

        // Add the owner
        if ($this->event->owner) {
            $recipients->push($this->event->owner->email);
        }

        // Add User-type attendees
        foreach ($this->event->attendees as $attendee) {
            if ($attendee->attendee_type === \App\Models\User::class && $attendee->attendee) {
                $recipients->push($attendee->attendee->email);
            }
        }

        $recipients = $recipients->unique()->filter();

        // Log for now — replace with Mail::queue() once Notification model is set up
        Log::info('SendEventReminderJob dispatched', [
            'event_id'   => $this->event->id,
            'title'      => $this->event->title,
            'start_at'   => $this->event->start_at,
            'recipients' => $recipients->values(),
        ]);
    }
}
