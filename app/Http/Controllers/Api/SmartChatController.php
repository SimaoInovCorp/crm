<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiSuggestion\SmartChatRequest;
use App\Services\SmartChatService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmartChatController extends Controller
{
    public function __construct(private SmartChatService $chatService) {}

    public function __invoke(SmartChatRequest $request): StreamedResponse|JsonResponse
    {
        $tenant   = app('current.tenant');
        $messages = $request->validated('messages');

        // Mock mode: return a fake response when OPENAI_MOCK=true (for UI testing without API credits)
        if (config('openai.mock', false)) {
            $mockContent = json_encode([
                'type'    => 'answer',
                'content' => 'This is a mock AI response. To enable real AI, add OpenAI API credits and set OPENAI_MOCK=false in your .env file.',
            ]);

            if ($request->boolean('stream', true)) {
                return response()->stream(function () use ($mockContent) {
                    echo "data: " . json_encode(['chunk' => $mockContent]) . "\n\n";
                    flush();
                    echo "data: " . json_encode(['done' => true, 'result' => ['type' => 'answer', 'content' => $mockContent]]) . "\n\n";
                    flush();
                }, 200, [
                    'Content-Type'      => 'text/event-stream',
                    'Cache-Control'     => 'no-cache',
                    'Connection'        => 'keep-alive',
                    'X-Accel-Buffering' => 'no',
                ]);
            }

            return response()->json([
                'type'    => 'answer',
                'content' => 'This is a mock AI response. To enable real AI, add OpenAI API credits and set OPENAI_MOCK=false in .env file.',
            ]);
        }

        if ($request->boolean('stream', true)) {
            return $this->stream($tenant, $messages);
        }

        $result = $this->chatService->chat($tenant, $messages);
        return response()->json($result);
    }

    private function stream($tenant, array $messages): StreamedResponse
    {
        return response()->stream(function () use ($tenant, $messages) {
            $buffer = '';
            foreach ($this->chatService->chatStream($tenant, $messages) as $chunk) {
                $buffer .= $chunk;
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

            // Process the full response for potential query execution
            $processed = $this->chatService->processStreamedResponse($tenant->id, $buffer);
            echo "data: " . json_encode(['done' => true, 'result' => $processed]) . "\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}