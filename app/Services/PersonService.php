<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonService
{
    public function index(array $filters = []): LengthAwarePaginator
    {
        $query = Person::query()->with('entity:id,name');

        if (! empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate(20);
    }

    public function create(array $data): Person
    {
        return Person::create($data);
    }

    public function update(Person $person, array $data): Person
    {
        $person->update($data);
        return $person->fresh(['entity']);
    }

    public function delete(Person $person): void
    {
        $person->delete();
    }

    public function show(Person $person): Person
    {
        return $person->load('entity:id,name');
    }

    /**
     * Return all people matching filters (no pagination) for CSV export.
     */
    public function exportAll(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Person::query()->with('entity:id,name');

        if (! empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Stream all people as a CSV download (SOLID: column/row logic lives here, not in the controller).
     */
    public function exportCsvStream(array $filters = []): StreamedResponse
    {
        $people = $this->exportAll($filters);

        return response()->streamDownload(function () use ($people) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBFsep=,\r\n");
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Position', 'Company', 'Notes', 'Created At']);

            foreach ($people as $person) {
                fputcsv($out, [
                    $person->id,
                    $person->name,
                    $person->email ?? '',
                    $person->phone ?? '',
                    $person->position ?? '',
                    $person->entity?->name ?? '',
                    $person->notes ?? '',
                    $person->created_at?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'people.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
