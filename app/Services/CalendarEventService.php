<?php

namespace App\Services;

use App\Jobs\SendEventReminderJob;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class CalendarEventService
{
    public function index(array $filters = []): LengthAwarePaginator
    {
        return CalendarEvent::with(['owner', 'attendees', 'entity', 'person', 'deal'])
            ->when(isset($filters['start']), fn ($q) => $q->where('start_at', '>=', $filters['start']))
            ->when(isset($filters['end']),   fn ($q) => $q->where('end_at',   '<=', $filters['end']))
            ->when(isset($filters['search']),fn ($q) => $q->where('title', 'like', '%'.$filters['search'].'%'))
            ->orderBy('start_at')
            ->paginate(50);
    }

    public function create(array $data): CalendarEvent
    {
        $attendees    = $data['attendees'] ?? [];
        $products     = $data['products'] ?? [];
        $notifyPerson = $data['notify_person'] ?? false;
        unset($data['attendees'], $data['products']);

        $event = CalendarEvent::create($data);

        $this->syncAttendees($event, $attendees);
        if (!empty($products)) {
            $this->syncProducts($event, $products);
        }
        $this->scheduleReminder($event);

        // Send immediate notification email to person if requested
        if ($notifyPerson && $event->person_id) {
            $event->load('person');
            $recipient = $event->person?->email;

            if ($recipient) {
                $this->sendPersonNotification($event, $recipient);
            }
        }

        return $event->load(['owner', 'attendees', 'entity', 'person', 'deal', 'calendarEventProducts.product']);
    }

    public function show(CalendarEvent $event): CalendarEvent
    {
        return $event->load(['owner', 'attendees.attendee', 'eventable', 'calendarEventProducts.product']);
    }

    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $attendees = array_key_exists('attendees', $data) ? $data['attendees'] : null;
        $products  = array_key_exists('products', $data)  ? $data['products']  : null;
        unset($data['attendees'], $data['products']);

        $event->update($data);

        if ($attendees !== null) {
            $this->syncAttendees($event, $attendees);
        }
        if ($products !== null) {
            $this->syncProducts($event, $products);
        }

        return $event->fresh(['owner', 'attendees', 'entity', 'person', 'deal', 'calendarEventProducts.product']);
    }

    public function delete(CalendarEvent $event): void
    {
        $event->attendees()->delete();
        $event->calendarEventProducts()->delete();
        $event->delete();
    }

    /**
     * Sync product lines for a calendar event. Replaces all existing lines.
     *
     * @param  array<array{product_id: int, quantity: int, unit_price: float}>  $products
     */
    public function syncProducts(CalendarEvent $event, array $products): void
    {
        $event->calendarEventProducts()->delete();

        foreach ($products as $item) {
            $event->calendarEventProducts()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $item['unit_price'],
            ]);
        }
    }

    private function syncAttendees(CalendarEvent $event, array $attendees): void
    {
        // attendees: [{ type: 'user'|'person', id: int }, ...]
        $event->attendees()->delete();

        foreach ($attendees as $attendee) {
            CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'attendee_type'     => $attendee['type'] === 'user'
                    ? \App\Models\User::class
                    : \App\Models\Person::class,
                'attendee_id'       => $attendee['id'],
            ]);
        }
    }

    private function scheduleReminder(CalendarEvent $event): void
    {
        // Dispatch reminder 30 minutes before event
        $reminderAt = $event->start_at->subMinutes(30);

        if ($reminderAt->isFuture()) {
            SendEventReminderJob::dispatch($event)->delay($reminderAt);
        }
    }

    private function sendPersonNotification(CalendarEvent $event, string $recipientEmail): void
    {
        $startFormatted = $event->start_at->format('d/m/Y H:i');
        $endFormatted   = $event->end_at->format('d/m/Y H:i');
        $location       = $event->location ? "<p><strong>Location:</strong> {$event->location}</p>" : '';
        $description    = $event->description ? "<p>{$event->description}</p>" : '';

        $body = "<h2>Event Reminder: {$event->title}</h2>"
            . "<p><strong>Start:</strong> {$startFormatted}</p>"
            . "<p><strong>End:</strong> {$endFormatted}</p>"
            . $location
            . $description;

        Mail::send([], [], function ($message) use ($recipientEmail, $event, $body) {
            $message->to($recipientEmail)
                ->subject("Event Invitation: {$event->title}")
                ->html($body);
        });
    }
}
