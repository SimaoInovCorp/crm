<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Person\StorePersonRequest;
use App\Http\Requests\Person\UpdatePersonRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonController extends Controller
{
    public function __construct(private PersonService $personService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Person::class);
        $people = $this->personService->index($request->only(['search', 'entity_id']));
        return PersonResource::collection($people);
    }

    public function store(StorePersonRequest $request): PersonResource
    {
        $this->authorize('create', Person::class);
        $person = $this->personService->create($request->validated());
        return new PersonResource($person);
    }

    public function show(Person $person): PersonResource
    {
        $this->authorize('view', $person);
        $person = $this->personService->show($person);
        return new PersonResource($person);
    }

    public function update(UpdatePersonRequest $request, Person $person): PersonResource
    {
        $this->authorize('update', $person);
        $person = $this->personService->update($person, $request->validated());
        return new PersonResource($person);
    }

    public function destroy(Person $person): JsonResponse
    {
        $this->authorize('delete', $person);
        $this->personService->delete($person);
        return response()->json(null, 204);
    }

    /**
     * GET /api/people/export
     * Stream all people for the current tenant as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Person::class);
        return $this->personService->exportCsvStream($request->only(['search', 'entity_id']));
    }

    public function sendEmail(Request $request, Person $person): JsonResponse
    {
        $this->authorize('view', $person);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:10000'],
        ]);

        if (! $person->email) {
            return response()->json(['message' => 'This person has no email address.'], 422);
        }

        $from    = config('mail.from.address');
        $fromName = config('mail.from.name');

        Mail::send([], [], function ($message) use ($person, $validated, $from, $fromName) {
            $message->to($person->email, $person->name)
                    ->from($from, $fromName)
                    ->subject($validated['subject'])
                    ->html(nl2br(e($validated['body'])));
        });

        return response()->json(['message' => 'Email sent successfully.']);
    }
}
