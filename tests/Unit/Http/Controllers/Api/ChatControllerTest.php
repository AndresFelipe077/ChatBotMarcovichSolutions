<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\ChatController;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\OpenMeteoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $geminiService;
    protected $meteoService;
    protected $chatController;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear mocks para los servicios
        /** @var GeminiService&Mockery\MockInterface $geminiService */
        $this->geminiService = Mockery::mock(GeminiService::class);
        /** @var OpenMeteoService&Mockery\MockInterface $meteoService */
        $this->meteoService = Mockery::mock(OpenMeteoService::class);

        // Crear instancia del controlador con los mocks
        $this->chatController = new ChatController($this->geminiService, $this->meteoService);

        // Crear usuario de prueba
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_all_chats_for_authenticated_user(): void
    {
        // Crear chats de prueba
        $chats = Chat::factory(3)->create(['user_id' => $this->user->id]);

        // Llamar al método index
        $response = $this->chatController->index();
        $data = $response->getData();

        // Verificar la respuesta
        $this->assertTrue($data->success);
        $this->assertCount(3, $data->data);
    }

    /** @test */
    public function it_creates_a_new_chat(): void
    {
        $request = new Request();

        $response = $this->chatController->store($request);
        $data = $response->getData();

        $this->assertTrue($data->success);
        $this->assertEquals('Nueva conversación', $data->data->title);
        $this->assertEquals(201, $response->status());
    }

    /** @test */
    public function it_shows_a_chat_with_messages(): void
    {
        $chat = Chat::factory()->create(['user_id' => $this->user->id]);
        $messages = Message::factory(2)->create(['chat_id' => $chat->id]);

        $response = $this->chatController->show($chat);
        $data = $response->getData();

        $this->assertTrue($data->success);
        $this->assertEquals($chat->id, $data->data->chat->id);
        $this->assertCount(2, $data->data->messages);
    }

    /** @test */
    public function it_handles_weather_queries(): void
    {
        // Configurar el mock de meteoService
        $this->meteoService->shouldReceive('getWeather')
            ->once()
            ->with('Bogotá', 'today')
            ->andReturn([
                'temperature' => 22.5,
                'weathercode' => 1,
                'precipitation' => 0,
                'is_tomorrow' => false
            ]);

        $this->meteoService->shouldReceive('interpretWeatherCode')
            ->once()
            ->with(1)
            ->andReturn('Despejado');

        $chat = Chat::factory()->create(['user_id' => $this->user->id]);
        $request = new Request([
            'content' => '¿Cómo está el clima en Bogotá?'
        ]);

        $response = $this->chatController->sendMessage($request, $chat);
        $data = $response->getData();

        $this->assertTrue($data->success);
        $this->assertStringContainsString('Clima en Bogotá', $data->data->message);
    }

    /** @test */
    public function it_handles_non_weather_messages(): void
    {
        $this->geminiService->shouldReceive('chat')
            ->once()
            ->andReturn('Esta es una respuesta de prueba');

        $chat = Chat::factory()->create(['user_id' => $this->user->id]);
        $request = new Request([
            'content' => 'Hola, ¿cómo estás?'
        ]);

        $response = $this->chatController->sendMessage($request, $chat);
        $data = $response->getData();

        $this->assertTrue($data->success);
        $this->assertEquals('Esta es una respuesta de prueba', $data->data->message);
    }

    /** @test */
    public function it_updates_chat_title_when_first_message_is_sent(): void
    {
        $this->geminiService->shouldReceive('chat')
            ->andReturn('Respuesta de prueba');

        $chat = Chat::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Nueva conversación'
        ]);

        $request = new Request([
            'content' => 'Este es un mensaje de prueba para el título'
        ]);

        $response = $this->chatController->sendMessage($request, $chat);
        $data = $response->getData();

        $this->assertNotEquals('Nueva conversación', $data->data->chat->title);
    }
}
