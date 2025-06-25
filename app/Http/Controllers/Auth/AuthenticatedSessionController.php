<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        Log::info('Iniciando proceso de autenticación', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'wants_json' => $request->wantsJson(),
            'is_api' => $request->is('api/*'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'x_requested_with' => $request->header('X-Requested-With'),
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            Log::warning('Credenciales inválidas', ['email' => $request->email]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas.',
                    'errors' => [
                        'email' => ['Las credenciales proporcionadas no son válidas.']
                    ]
                ], 401, ['Content-Type' => 'application/json']);
            }

            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        $user = Auth::user();
        Log::info('Usuario autenticado', ['user_id' => $user->id, 'email' => $user->email]);

        if ($request->wantsJson() || $request->is('api/*')) {
            try {
                $revoked = $user->tokens()->delete();
                Log::info('Tokens revocados', ['count' => $revoked]);

                // Crear nuevo token
                $token = $user->createToken('api-token', ['*'])->plainTextToken;
                Log::info('Nuevo token generado', ['token' => '***' . substr($token, -8)]);

                return response()->json([
                    'success' => true,
                    'message' => 'Inicio de sesión exitoso',
                    'data' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Error al generar token', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el token de autenticación.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor.'
                ], 500);
            }
        }

        $request->session()->regenerate();
        Log::info('Autenticación web exitosa', ['user_id' => $user->id]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
