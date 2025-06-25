<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\GeminiService;
use App\Services\OpenMeteoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected GeminiService $gemini;
    protected OpenMeteoService $meteo;

    public function __construct(GeminiService $gemini, OpenMeteoService $meteo)
    {
        $this->gemini = $gemini;
        $this->meteo  = $meteo;
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $chats = $user->chats()->with('messages')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $chats
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $chat = $user->chats()->create([
            'title' => 'Nueva conversación',
        ]);

        return response()->json([
            'success' => true,
            'data' => $chat
        ], 201);
    }

    public function show(Chat $chat): JsonResponse
    {
        $this->authorize('view', $chat);
        $chat->load('messages');

        return response()->json([
            'success' => true,
            'data' => $chat
        ]);
    }

    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $this->authorize('update', $chat);

        $request->validate([
            'content' => 'required|string',
        ]);

        $userMessage = $chat->messages()->create([
            'content' => $request->content,
            'role'    => 'user',
        ]);

        try {
            $messages = $chat->messages()->orderBy('created_at')->pluck('content')->toArray();
            $messages[] = $request->content;

            $aiResponse = $this->gemini->chat($messages);

            $aiMessage = $chat->messages()->create([
                'content' => $aiResponse,
                'role'    => 'assistant',
            ]);

            if ($chat->title === 'Nueva conversación') {
                $chat->update([
                    'title' => $this->generateSimpleTitle($request->content)
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'message' => $aiMessage,
                    'chat'    => $chat->fresh()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error enviando mensaje a Gemini: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud'
            ], 500);
        }
    }

    protected function generateSimpleTitle(string $text): string
    {
        $words = explode(' ', trim($text));
        return ucfirst(implode(' ', array_slice($words, 0, 4))) . (count($words) > 4 ? '...' : '');
    }
}
