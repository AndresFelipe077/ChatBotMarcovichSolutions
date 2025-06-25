<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function chat($messages): mixed
    {
        $apiKey = config('services.gemini.api_key');

        $formattedMessages = array_map(fn($m) => [
            'role' => 'user',
            'parts' => [['text' => $m]]
        ], $messages);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
            'contents' => $formattedMessages
        ]);

        info('Gemini API Response:', $response->json());

        if ($response->failed()) {
            Log::error('Gemini API Error:', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return "Lo siento, no pude generar una respuesta en este momento. Por favor, inténtalo de nuevo más tarde.";
        }

        $responseData = $response->json();

        return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No se pudo obtener una respuesta del asistente.';
    }
}
