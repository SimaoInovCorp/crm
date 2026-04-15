<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Entity;
use App\Models\Person;
use Illuminate\Http\UploadedFile;

class CsvImportService
{
    /**
     * Import a CSV file for the given resource type (entities | people | deals).
     * Returns an array with counts of imported rows and any per-row errors.
     */
    public function import(string $type, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['imported' => 0, 'errors' => ['Could not open file.']];
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);
            return ['imported' => 0, 'errors' => ['File is empty or has no header row.']];
        }

        $headers  = array_map('trim', array_map('strtolower', $headers));
        $imported = 0;
        $errors   = [];
        $row      = 2; // 1 = header

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                $errors[] = "Row {$row}: column count mismatch.";
                $row++;
                continue;
            }

            $record = array_combine($headers, array_map('trim', $data));

            try {
                match ($type) {
                    'entities' => $this->importEntity($record),
                    'people'   => $this->importPerson($record),
                    'deals'    => $this->importDeal($record),
                };
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
            }

            $row++;
        }

        fclose($handle);

        return ['imported' => $imported, 'errors' => $errors];
    }

    // -------------------------------------------------------------------------
    // Expected CSV columns
    // -------------------------------------------------------------------------
    // Entities : name*, email, phone, vat, address, status
    // People   : name*, email, phone, position, notes, entity_id
    // Deals    : title*, value, stage, probability, expected_close_date, entity_id, person_id, owner_id
    // -------------------------------------------------------------------------

    private function importEntity(array $row): void
    {
        if (empty($row['name'])) {
            throw new \InvalidArgumentException('Missing required column: name');
        }

        $tenantId = app('current.tenant')->id;

        Entity::create([
            'tenant_id' => $tenantId,
            'name'      => $row['name'],
            'email'     => $row['email'] ?? null,
            'phone'     => $row['phone'] ?? null,
            'vat'       => $row['vat'] ?? null,
            'address'   => $row['address'] ?? null,
            'status'    => $row['status'] ?? 'active',
        ]);
    }

    private function importPerson(array $row): void
    {
        if (empty($row['name'])) {
            throw new \InvalidArgumentException('Missing required column: name');
        }

        $tenantId = app('current.tenant')->id;

        Person::create([
            'tenant_id' => $tenantId,
            'name'      => $row['name'],
            'email'     => $row['email'] ?? null,
            'phone'     => $row['phone'] ?? null,
            'position'  => $row['position'] ?? null,
            'notes'     => $row['notes'] ?? null,
            'entity_id' => ! empty($row['entity_id']) ? (int) $row['entity_id'] : null,
        ]);
    }

    private function importDeal(array $row): void
    {
        if (empty($row['title'])) {
            throw new \InvalidArgumentException('Missing required column: title');
        }

        $tenant   = app('current.tenant');
        $tenantId = $tenant->id;
        $ownerId  = ! empty($row['owner_id']) ? (int) $row['owner_id'] : $tenant->owner_id;

        Deal::create([
            'tenant_id'           => $tenantId,
            'title'               => $row['title'],
            'value'               => ! empty($row['value']) ? (float) $row['value'] : 0,
            'stage'               => $row['stage'] ?? 'lead',
            'probability'         => ! empty($row['probability']) ? (int) $row['probability'] : 0,
            'expected_close_date' => ! empty($row['expected_close_date']) ? $row['expected_close_date'] : null,
            'entity_id'           => ! empty($row['entity_id']) ? (int) $row['entity_id'] : null,
            'person_id'           => ! empty($row['person_id']) ? (int) $row['person_id'] : null,
            'owner_id'            => $ownerId,
        ]);
    }
}
