<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\AIPlatform\V1\PredictionServiceClient;
use Google\Cloud\AIPlatform\V1\Content;
use Google\Cloud\AIPlatform\V1\GenerateContentResponse;
use Google\Cloud\AIPlatform\V1\GenerationConfig;
use Google\Cloud\AIPlatform\V1\SafetySetting;
use Google\Cloud\AIPlatform\V1\HarmCategory;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $predictionServiceClient;
    protected $modelName;

    public function __construct()
    {
        $this->modelName = 'projects/your-project-id/locations/us-central1/publishers/google/models/gemini-1.5-pro';
        $this->predictionServiceClient = new PredictionServiceClient([
            'credentials' => config('services.google_cloud.credentials')
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        $chats = $user->chats()->with('messages')->latest()->get();
        
        return response()->json($chats);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $chat = $user->chats()->create([
            'title' => 'Nueva conversación',
        ]);

        return response()->json($chat, 201);
    }

    public function show(Chat $chat)
    {
        $this->authorize('view', $chat);
        
        $chat->load('messages');
        return response()->json($chat);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);
        
        $request->validate([
            'content' => 'required|string',
        ]);

        // Save user message
        $userMessage = $chat->messages()->create([
            'content' => $request->content,
            'role' => 'user',
        ]);

        // Get chat history for context
        $history = $chat->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'parts' => [['text' => $message->content]]
                ];
            })
            ->toArray();

        try {
            // Generate response using Gemini
            $response = $this->generateContent($request->content, $history);
            
            // Save AI response
            $aiMessage = $chat->messages()->create([
                'content' => $response,
                'role' => 'assistant',
            ]);

            // Update chat title if it's the first message
            if ($chat->title === 'Nueva conversación') {
                $chat->update([
                    'title' => $this->generateChatTitle($request->content)
                ]);
            }

            return response()->json([
                'message' => $aiMessage,
                'chat' => $chat->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar la solicitud',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function generateContent(string $prompt, array $history = [])
    {
        $contents = [];
        
        // Add system prompt for weather-related queries
        $systemPrompt = "Eres un asistente de clima útil. Cuando el usuario pregunte sobre el clima, 
        intenta extraer la ubicación y la fecha de la consulta. Si la consulta es sobre el clima, 
        responde con información detallada. Si la ubicación no está clara, pide aclaración. 
        Para consultas que no son sobre el clima, responde de manera general.";
        
        $contents[] = new Content([
            'role' => 'user',
            'parts' => [['text' => $systemPrompt]]
        ]);
        
        $contents[] = new Content([
            'role' => 'model',
            'parts' => [['text' => 'Entendido, estoy listo para ayudarte con información sobre el clima.']]
        ]);
        
        // Add conversation history
        foreach ($history as $message) {
            $contents[] = new Content([
                'role' => $message['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $message['parts'][0]['text']]]
            ]);
        }
        
        // Add current user message
        $contents[] = new Content([
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ]);

        $generationConfig = new GenerationConfig([
            'temperature' => 0.7,
            'max_output_tokens' => 2048,
            'top_p' => 0.8,
            'top_k' => 40,
        ]);

        $safetySettings = [
            new SafetySetting([
                'category' => HarmCategory::HARM_CATEGORY_HATE_SPEECH,
                'threshold' => SafetySetting::HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
            ]),
            new SafetySetting([
                'category' => HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
                'threshold' => SafetySetting::HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
            ]),
            new SafetySetting([
                'category' => HarmCategory::HARM_CATEGORY_SEXUALLY_EXPLICIT,
                'threshold' => SafetySetting::HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
            ]),
            new SafetySetting([
                'category' => HarmCategory::HARM_CATEGORY_HARASSMENT,
                'threshold' => SafetySetting::HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
            ]),
        ];

        try {
            $response = $this->predictionServiceClient->generateContent(
                $this->modelName,
                $contents,
                [
                    'generationConfig' => $generationConfig,
                    'safetySettings' => $safetySettings,
                ]
            );

            return $response->getCandidates()[0]->getContent()->getParts()[0]->getText();

        } catch (\Exception $e) {
            \Log::error('Error generating content with Gemini: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateChatTitle(string $firstMessage): string
    {
        $prompt = "Genera un título corto (máximo 5 palabras) para esta conversación: " . $firstMessage;
        
        try {
            $response = $this->predictionServiceClient->generateContent(
                $this->modelName,
                [
                    new Content([
                        'role' => 'user',
                        'parts' => [['text' => $prompt]]
                    ])
                ],
                [
                    'generationConfig' => new GenerationConfig([
                        'temperature' => 0.3,
                        'max_output_tokens' => 50,
                    ])
                ]
            );

            $title = $response->getCandidates()[0]->getContent()->getParts()[0]->getText();
            return trim($title, '\"\'');
            
        } catch (\Exception $e) {
            \Log::error('Error generating chat title: ' . $e->getMessage());
            return 'Nueva conversación';
        }
    }
}

# cGFuZ29saW4=
