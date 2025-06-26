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
        $chats = $user->chats()->latest()->get();

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

        $chat->load(['messages' => function($query): void {
            $query->orderBy('created_at', 'asc');
        }]);

        return response()->json([
            'success' => true,
            'data'    => [
                'chat'     => $chat,
                'messages' => $chat->messages
            ]
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
            $userInput = $request->content;
            $weatherResponse = $this->handleWeatherQuery($userInput);

            if ($weatherResponse) {
                $aiMessage = $chat->messages()->create([
                    'content' => $weatherResponse,
                    'role'    => 'assistant',
                    'is_weather' => true
                ]);
            } else {
                $messages = $chat->messages()
                    ->where('is_weather', false)
                    ->orderBy('created_at')
                    ->pluck('content')
                    ->toArray();

                $messages[] = $userInput;
                $aiResponse = $this->gemini->chat($messages);

                $aiMessage = $chat->messages()->create([
                    'content' => $aiResponse,
                    'role'    => 'assistant',
                    'is_weather' => false
                ]);
            }


            if ($chat->title === 'Nueva conversación') {
                $chat->update([
                    'title' => $this->generateSimpleTitle($request->content)
                ]);
            }


            return response()->json([
                'success' => true,
                'data'    => [
                    'chat_id' => $chat->id,
                    'message' => $aiMessage->content,
                    'chat'    => $chat->fresh()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error processing chat message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    protected function handleWeatherQuery(string $message): ?string
    {
        // Check for weather-related keywords in Spanish
        $weatherKeywords = ['clima', 'tiempo', 'temperatura', 'llover', 'lluvia', 'paraguas', 'soleado', 'nublado'];
        $hasWeatherKeyword = preg_match('/\b(' . implode('|', $weatherKeywords) . ')\b/i', strtolower($message));

        if (!$hasWeatherKeyword) {
            return null;
        }

        // Extract city name using a simple pattern
        $cityPattern = '/(?:en|para|de|el clima en|el tiempo en)\s+([^\s,.!?]+(?:\s+[^\s,.!?]+)*)/i';
        $matches = [];

        if (preg_match($cityPattern, strtolower($message), $matches)) {
            $city = trim($matches[1]);
        } else {
            // Default city if none specified
            $city = 'Bogotá';
        }

        // Check if asking for tomorrow's weather
        $isTomorrow = preg_match('/mañana|pasado mañana|día siguiente|tomorrow/i', $message);
        $date = $isTomorrow ? 'mañana' : 'hoy';

        try {
            $weatherData = $this->meteo->getWeather($city, $isTomorrow ? 'mañana' : 'today');

            if (!$weatherData) {
                return "No pude obtener la información del tiempo para {$city}. Por favor, intenta con otra ciudad o más tarde.";
            }

            $weatherCondition = $this->meteo->interpretWeatherCode($weatherData['weathercode']);
            $emoji = $this->getWeatherEmoji($weatherData['weathercode']);

            $response = "{$emoji} Clima en {$city} " . ($weatherData['is_tomorrow'] ? '(mañana)' : '(hoy)') . ":\n";
            $response .= "- Temperatura: " . round($weatherData['temperature']) . "°C\n";
            $response .= "- Condición: {$weatherCondition}\n";

            // Add umbrella recommendation if relevant
            if ($weatherData['precipitation'] > 0) {
                $response .= "- Lluvia: " . round($weatherData['precipitation'], 1) . " mm\n";
                if (strpos(strtolower($message), 'paraguas') !== false) {
                    $response .= "\n¡Sí, lleva paraguas! " . $this->getUmbrellaEmoji() . "\n";
                }
            } elseif (strpos(strtolower($message), 'paraguas') !== false) {
                $response .= "\nNo parece que vaya a llover, no necesitarás paraguas. " . $this->getSunEmoji() . "\n";
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Error getting weather: ' . $e->getMessage());
            return null; // Let Gemini handle the response
        }
    }

    protected function getWeatherEmoji(int $weatherCode): string
    {
        // Map weather codes to emojis
        if ($weatherCode >= 0 && $weatherCode <= 3) {
            return '☀️'; // Clear to overcast
        } elseif ($weatherCode >= 45 && $weatherCode <= 48) {
            return '🌫️'; // Fog
        } elseif (($weatherCode >= 51 && $weatherCode <= 67) || ($weatherCode >= 80 && $weatherCode <= 82)) {
            return '🌧️'; // Rain or showers
        } elseif ($weatherCode >= 71 && $weatherCode <= 77) {
            return '❄️'; // Snow
        } elseif ($weatherCode >= 95 && $weatherCode <= 99) {
            return '⛈️'; // Thunderstorm
        }
        return '🌡️'; // Default thermometer
    }

    protected function getUmbrellaEmoji(): string
    {
        $umbrellas = ['☔', '🌂', '🌧️'];
        return $umbrellas[array_rand($umbrellas)];
    }

    protected function getSunEmoji(): string
    {
        $suns = ['☀️', '🌞', '😎'];
        return $suns[array_rand($suns)];
    }

    protected function generateSimpleTitle(string $text): string
    {
        $words = explode(' ', trim($text));
        return ucfirst(implode(' ', array_slice($words, 0, 4))) . (count($words) > 4 ? '...' : '');
    }
}
