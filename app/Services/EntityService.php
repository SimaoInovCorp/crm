<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntityService
{
    public function index(array $filters = []): LengthAwarePaginator
    {
        $query = Entity::query()->withCount(['people']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vat', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name')->paginate(20);
    }

    public function create(array $data): Entity
    {
        return Entity::create($data);
    }

    public function update(Entity $entity, array $data): Entity
    {
        $entity->update($data);
        return $entity->fresh();
    }

    public function delete(Entity $entity): void
    {
        $entity->delete();
    }

    public function show(Entity $entity): Entity
    {
        return $entity->loadCount('people');
    }

    /**
     * Return all entities matching filters (no pagination) for CSV export.
     */
    public function exportAll(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Entity::query()->withCount(['people']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vat', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Stream all entities as a CSV download (SOLID: column/row logic lives here, not in the controller).
     */
    public function exportCsvStream(array $filters = []): StreamedResponse
    {
        $entities = $this->exportAll($filters);

        return response()->streamDownload(function () use ($entities) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBFsep=,\r\n");
            fputcsv($out, ['ID', 'Name', 'VAT', 'Email', 'Phone', 'Status', 'Address', 'People Count', 'Created At']);

            foreach ($entities as $entity) {
                fputcsv($out, [
                    $entity->id,
                    $entity->name,
                    $entity->vat ?? '',
                    $entity->email ?? '',
                    $entity->phone ?? '',
                    $entity->status,
                    $entity->address ?? '',
                    $entity->people_count ?? 0,
                    $entity->created_at?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'entities.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
