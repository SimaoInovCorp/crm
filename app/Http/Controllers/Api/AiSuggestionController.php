<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiSuggestion\PostponeAiSuggestionRequest;
use App\Http\Resources\AiSuggestionResource;
use App\Models\AiSuggestion;
use App\Services\AiSalesAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiSuggestionController extends Controller
{
    public function __construct(private AiSalesAgentService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AiSuggestion::class);

        $suggestions = $this->service->listSuggestions(
            $request->only(['status', 'deal_id'])
        );

        return AiSuggestionResource::collection($suggestions);
    }

    public function accept(AiSuggestion $aiSuggestion): AiSuggestionResource
    {
        $this->authorize('accept', $aiSuggestion);
        $suggestion = $this->service->accept($aiSuggestion);
        return new AiSuggestionResource($suggestion);
    }

    public function dismiss(AiSuggestion $aiSuggestion): AiSuggestionResource
    {
        $this->authorize('dismiss', $aiSuggestion);
        $suggestion = $this->service->dismiss($aiSuggestion);
        return new AiSuggestionResource($suggestion);
    }

    public function postpone(PostponeAiSuggestionRequest $request, AiSuggestion $aiSuggestion): AiSuggestionResource
    {
        $this->authorize('postpone', $aiSuggestion);
        $days = $request->validated('days', 3);
        $suggestion = $this->service->postpone($aiSuggestion, $days);
        return new AiSuggestionResource($suggestion);
    }
}