<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CalendarEventController extends Controller
{
    public function __construct(
        private CalendarEventService $calendarEventService,
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CalendarEvent::class);
        $events = $this->calendarEventService->index(
            $request->only(['start', 'end', 'search'])
        );
        return CalendarEventResource::collection($events);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $this->authorize('create', CalendarEvent::class);
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $event = $this->calendarEventService->create($data);
        return (new CalendarEventResource($event))->response()->setStatusCode(201);
    }

    public function show(CalendarEvent $calendarEvent): CalendarEventResource
    {
        $this->authorize('view', $calendarEvent);
        $event = $this->calendarEventService->show($calendarEvent);
        return new CalendarEventResource($event);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): CalendarEventResource
    {
        $this->authorize('update', $calendarEvent);
        $event = $this->calendarEventService->update($calendarEvent, $request->validated());
        return new CalendarEventResource($event);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $this->authorize('delete', $calendarEvent);
        $this->calendarEventService->delete($calendarEvent);
        return response()->json(null, 204);
    }

    /**
     * Generate and email a cryptographically signed PDF invoice
     * for the deal linked to this calendar event.
     */
    public function sendInvoice(CalendarEvent $calendarEvent): JsonResponse
    {
        $this->authorize('view', $calendarEvent);

        try {
            $invoiceNumber = $this->invoiceService->sendInvoice($calendarEvent);
            return response()->json(['message' => "Invoice {$invoiceNumber} sent successfully."]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
