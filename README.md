# Laravel + Vue.js + JWT + Gemini AI - Guía Completa

Este proyecto combina Laravel 10, Vue.js 3, JWT para autenticación y la API de Google Gemini para funcionalidades de IA.

## Requisitos Previos

- PHP >= 8.1
- Composer
- Node.js >= 16.0 y npm >= 8.0
- Base de datos MySQL/PostgreSQL/SQLite
- Cuenta en Google Cloud con Gemini AI habilitado
- Git (opcional pero recomendado)

## Configuración Inicial

### 1. Clonar el repositorio
```bash
git clone [https://github.com/AndresFelipe077/ChatBotMarcovichSolutions.git]
cd ChatBotMarcovichSolutions
```

### 2. Instalar dependencias PHP
```bash
composer install
```

### 3. Configurar el entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configuración de la base de datos
1. Crea una base de datos en tu servidor de base de datos
2. Configura las variables de entorno en el archivo `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Configurar JWT
```bash
composer require php-open-source-saver/jwt-auth
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 6. Configurar Gemini AI
1. Obtén tu API key de Google Gemini AI
2. Agrega la siguiente variable a tu archivo `.env`:
```env
GEMINI_API_KEY=tu_api_key_de_gemini
```

## Configuración de Vue.js

### 1. Instalar dependencias de Node.js
```bash
npm install
```

### 2. Compilar assets para desarrollo
```bash
npm run dev
```

### 3. Para producción
```bash
npm run build
```

## Estructura del Proyecto

```
/
├── app/                  # Código fuente de Laravel
│   ├── Http/Controllers/ # Controladores
│   ├── Models/           # Modelos
│   └── ...
├── config/              # Archivos de configuración
├── database/            # Migraciones y seeders
├── public/              # Punto de entrada de la aplicación
├── resources/
│   ├── js/             # Código fuente de Vue.js
│   │   ├── components/    # Componentes Vue
│   │   ├── router/       # Rutas de Vue
│   │   └── app.js        # Punto de entrada de Vue
│   └── views/           # Vistas de Blade
└── routes/              # Rutas de la API y web
```

## Configuración de Autenticación JWT

### 1. Configurar el modelo de Usuario
Asegúrate de que tu modelo `User` implemente `JWTSubject`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // ... resto del código del modelo ...

    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

### 2. Configuración de rutas de autenticación
En `routes/api.php`:

```php
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->group(function() {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('me', 'me');
});
```

## Integración con Gemini AI

### 1. Instalar el SDK de Google AI
```bash
composer require google/ai
```

### 2. Crear un servicio para Gemini
Crea un nuevo servicio en `app/Services/GeminiService.php`:

```php
<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\AIPlatform;

class GeminiService
{
    protected $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig([
            'key' => config('services.gemini.key')
        ]);
    }

    public function generateText($prompt)
    {
        // Implementar la lógica para generar texto con Gemini
    }
}
```

## Despliegue

### 1. Configuración de producción
Asegúrate de que tu archivo `.env` tenga la configuración correcta para producción:
```env
APP_ENV=production
APP_DEBUG=false

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 2. Optimización
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Comandos útiles

- **Iniciar servidor de desarrollo:** `php artisan serve`
- **Compilar assets:** `npm run dev` (desarrollo) o `npm run build` (producción)
- **Ejecutar migraciones:** `php artisan migrate`
- **Ejecutar seeders:** `php artisan db:seed`
- **Limpiar caché:** `php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear`

## Solución de problemas

### Problemas con JWT
- Si recibes "Token not provided", verifica que estés enviando el token en el header `Authorization: Bearer {token}`
- Si el token expira muy rápido, ajusta `JWT_TTL` en `.env`

### Problemas con Vue
- Si los cambios no se reflejan, intenta:
  - Detener el servidor de desarrollo
  - Ejecutar `npm run dev` nuevamente
  - Limpiar la caché del navegador

## Contribución

1. Haz un fork del proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Haz commit de tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Haz push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

---

Desarrollado con ❤️ por [Andrés Felipe Pizo Luligo]
