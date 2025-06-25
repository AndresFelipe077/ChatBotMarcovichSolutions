<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{

    public function ask(Request $request, GeminiService $gemini): JsonResponse
    {
        $userMessage = $request->input('message');

        $messages = [
            "Eres un asistente experto en clima. Si el usuario pregunta por el clima, responde con claridad. Usa datos externos si es necesario.",
            $userMessage
        ];

        $response = $gemini->chat($messages);

        // Guarda en DB si quieres...

        return response()->json([
            'reply' => $response
        ]);
    }
}
