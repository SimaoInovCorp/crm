<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entity\StoreEntityRequest;
use App\Http\Requests\Entity\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntityController extends Controller
{
    public function __construct(private EntityService $entityService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Entity::class);
        $entities = $this->entityService->index($request->only(['search', 'status']));
        return EntityResource::collection($entities);
    }

    public function store(StoreEntityRequest $request): EntityResource
    {
        $this->authorize('create', Entity::class);
        $entity = $this->entityService->create($request->validated());
        return new EntityResource($entity);
    }

    public function show(Entity $entity): EntityResource
    {
        $this->authorize('view', $entity);
        $entity = $this->entityService->show($entity);
        return new EntityResource($entity);
    }

    public function update(UpdateEntityRequest $request, Entity $entity): EntityResource
    {
        $this->authorize('update', $entity);
        $entity = $this->entityService->update($entity, $request->validated());
        return new EntityResource($entity);
    }

    public function destroy(Entity $entity): JsonResponse
    {
        $this->authorize('delete', $entity);
        $this->entityService->delete($entity);
        return response()->json(null, 204);
    }

    /**
     * GET /api/entities/export
     * Stream all entities for the current tenant as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Entity::class);
        return $this->entityService->exportCsvStream($request->only(['search', 'status']));
    }

    public function sendEmail(Request $request, Entity $entity): JsonResponse
    {
        $this->authorize('view', $entity);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:10000'],
        ]);

        if (! $entity->email) {
            return response()->json(['message' => 'This entity has no email address.'], 422);
        }

        $from     = config('mail.from.address');
        $fromName = config('mail.from.name');

        Mail::send([], [], function ($message) use ($entity, $validated, $from, $fromName) {
            $message->to($entity->email, $entity->name)
                    ->from($from, $fromName)
                    ->subject($validated['subject'])
                    ->html(nl2br(e($validated['body'])));
        });

        return response()->json(['message' => 'Email sent successfully.']);
    }
}
