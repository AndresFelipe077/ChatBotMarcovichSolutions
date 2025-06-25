<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
class GeminiService
{
    public function chat($messages): mixed
    {
        $apiKey = config('services.gemini.api_key');

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$apiKey", [
            'contents' => [
                [
                    'parts' => array_map(fn($m) => ['text' => $m], $messages)
                ]
            ]
        ]);

        if ($response->failed()) {
            return "Lo siento, no pude generar una respuesta.";
        }

        return $response->json('candidates.0.content.parts.0.text');
    }
}
