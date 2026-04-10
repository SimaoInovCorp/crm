<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deal\SendProposalRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;

class ProposalController extends Controller
{
    public function __construct(private ProposalService $service) {}

    public function store(SendProposalRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            $deal = $this->service->upload($deal, $request->file('file'), $data['recipient_email'] ?? null, $data['body'] ?? null);
        }

        if (! empty($data['send'])) {
            $this->service->send($deal, $data['recipient_email'], $data['subject'], $data['body']);
        }

        return (new DealResource($deal->fresh()))->response();
    }
}
