<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CsvImportRequest;
use App\Services\CsvImportService;
use Illuminate\Http\JsonResponse;

class CsvImportController extends Controller
{
    public function __construct(private CsvImportService $service) {}

    /**
     * POST /api/import/{type}
     * Accepts a CSV file and imports rows into the given resource type.
     * type: entities | people | deals
     */
    public function store(CsvImportRequest $request, string $type): JsonResponse
    {
        $result = $this->service->import($type, $request->file('file'));

        $status = empty($result['errors']) ? 200 : 422;

        return response()->json([
            'imported' => $result['imported'],
            'errors'   => $result['errors'],
            'message'  => $result['imported'] . ' row(s) imported successfully.'
                . (count($result['errors']) ? ' Some rows were skipped due to errors.' : ''),
        ], $status);
    }
}
