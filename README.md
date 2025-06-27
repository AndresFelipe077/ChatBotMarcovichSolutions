# ChatBot con Laravel, Vue.js y Gemini AI

Guía de instalación y uso rápido para evaluadores.

## Requisitos Previos

- PHP >= 8.1
- Composer
- Node.js >= 16.0 y npm >= 8.0
- MySQL/MariaDB
- API Key de Google Gemini AI

## Instalación Rápida

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/AndresFelipe077/ChatBotMarcovichSolutions.git
   cd ChatBotMarcovichSolutions
   ```

2. **Instalar dependencias PHP**
   ```bash
   composer install
   ```

3. **Configurar entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos**
   - Crear una base de datos MySQL
   - Configurar las credenciales en `.env`:
     ```
     DB_DATABASE=nombre_bd
     DB_USERNAME=usuario
     DB_PASSWORD=contraseña
     ```

5. **Configurar JWT**
   ```bash
   php artisan jwt:secret
   ```

6. **Configurar Gemini AI**
   Agregar en `.env`:
   ```
   GEMINI_API_KEY=tu_api_key_de_gemini
   ```

7. **Instalar dependencias de frontend**
   ```bash
   npm install
   ```

## Ejecutar la Aplicación

1. **Ejecutar migraciones**
   ```bash
   php artisan migrate --seed
   ```

2. **Iniciar servidor backend**
   ```bash
   php artisan serve
   ```

3. **Compilar frontend** (en otra terminal)
   ```bash
   npm run dev
   ```

4. **Acceder a la aplicación**
   Abre tu navegador en: `http://localhost:8000`

## Credenciales de Prueba

- **Email:** test@gmail.com
- **Contraseña:** 12345678

## Endpoints de la API

- `POST /api/register` - Registro de usuario
- `POST /api/login` - Inicio de sesión (obtener JWT)
- `GET /api/me` - Perfil de usuario (requiere autenticación)
- `POST /api/chat` - Enviar mensaje al chatbot

## Variables de Entorno Importantes

```
APP_NAME=ChatBot
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatbot
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=tu_jwt_secret
JWT_TTL=60

GEMINI_API_KEY=tu_api_key_de_gemini
```

## Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recompilar frontend
npm run dev

# Crear enlace de almacenamiento
php artisan storage:link
```

## Solución de Problemas Comunes

- **Error de conexión a la base de datos**: Verifica las credenciales en `.env`
- **Error 419 en formularios**: Agrega `@csrf` en los formularios o verifica la sesión
- **Problemas con JWT**: Asegúrate de incluir el token en el header `Authorization: Bearer {token}`

---

Desarrollado con ❤️ por Andrés Felipe Pizo Luligo
