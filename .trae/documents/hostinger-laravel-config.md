# 🏠 Configuração Laravel no Hostinger

## 📋 Preparação do Laravel para Host Compartilhado

### 1. Estrutura de Arquivos no Hostinger

```
public_html/
├── .htaccess                 # Redirecionamento para public
├── index.php                 # Arquivo de entrada
├── app/                      # Aplicação Laravel
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/                  # Logs e cache
├── vendor/                   # Dependências Composer
├── .env                      # Configurações de produção
├── artisan
├── composer.json
└── composer.lock
```

### 2. Arquivo `.htaccess` Principal

**`public_html/.htaccess`**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirecionar para pasta public do Laravel
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,QSA]
</IfModule>

# Configurações de segurança
<Files .env>
    Order allow,deny
    Deny from all
</Files>

<Files composer.json>
    Order allow,deny
    Deny from all
</Files>

<Files composer.lock>
    Order allow,deny
    Deny from all
</Files>

# Bloquear acesso a pastas sensíveis
RedirectMatch 403 ^/storage/.*$
RedirectMatch 403 ^/bootstrap/cache/.*$
```

### 3. Arquivo `public/.htaccess`

**`public_html/public/.htaccess`**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Configurações de cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>

# Compressão GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## 🔧 Configuração do Ambiente (.env)

### Arquivo `.env` para Produção

```env
# Aplicação
APP_NAME="WhatsApp Manager"
APP_ENV=production
APP_KEY=base64:SUA_CHAVE_LARAVEL_AQUI
APP_DEBUG=false
APP_URL=https://seudominio.com

# Database Hostinger
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_whatsapp
DB_USERNAME=u123456789_user
DB_PASSWORD=SuaSenhaSegura123!

# Cache e Sessões
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail (usando SMTP do Hostinger)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@seudominio.com
MAIL_PASSWORD=SuaSenhaEmail123!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# WhatsApp API (Vercel)
WA_SERVER_URL=https://sua-api-whatsapp.vercel.app
WA_SERVER_HOST=sua-api-whatsapp.vercel.app
WA_SERVER_PORT=443
WA_API_SECRET=sua_chave_secreta_api

# URLs do Cliente
CLIENT_SERVER_URL=https://seudominio.com
CLIENT_SERVER_HOST=seudominio.com
CLIENT_SERVER_PORT=443

# Configurações adicionais
BROADCAST_DRIVER=log
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Timezone
APP_TIMEZONE=America/Sao_Paulo
```

## 🔌 Configuração do Service Provider

### `app/Providers/WhatsAppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsAppService;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService(
                config('services.whatsapp.url'),
                config('services.whatsapp.secret')
            );
        });
    }

    public function boot()
    {
        //
    }
}
```

### `config/services.php`

```php
<?php

return [
    // ... outras configurações

    'whatsapp' => [
        'url' => env('WA_SERVER_URL', 'http://localhost:8000'),
        'host' => env('WA_SERVER_HOST', 'localhost'),
        'port' => env('WA_SERVER_PORT', 8000),
        'secret' => env('WA_API_SECRET'),
        'timeout' => 30,
    ],

    'client' => [
        'url' => env('CLIENT_SERVER_URL', 'http://localhost:8080'),
        'host' => env('CLIENT_SERVER_HOST', 'localhost'),
        'port' => env('CLIENT_SERVER_PORT', 8080),
    ],
];
```

## 🌐 Service de Comunicação com API

### `app/Services/WhatsAppService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppService
{
    private $baseUrl;
    private $secret;
    private $timeout;

    public function __construct($baseUrl = null, $secret = null)
    {
        $this->baseUrl = $baseUrl ?: config('services.whatsapp.url');
        $this->secret = $secret ?: config('services.whatsapp.secret');
        $this->timeout = config('services.whatsapp.timeout', 30);
    }

    /**
     * Criar nova sessão WhatsApp
     */
    public function createSession($sessionId = null)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/api/sessions", [
                    'sessionId' => $sessionId
                ]);

            $data = $response->json();

            if ($response->successful() && $data['success']) {
                // Cache do resultado por 5 minutos
                Cache::put("whatsapp_session_{$data['sessionId']}", $data, 300);
                
                Log::info('WhatsApp session created', [
                    'sessionId' => $data['sessionId'],
                    'status' => $data['status'] ?? 'unknown'
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error creating WhatsApp session', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obter status da sessão
     */
    public function getSessionStatus($sessionId)
    {
        try {
            // Verificar cache primeiro
            $cached = Cache::get("whatsapp_session_{$sessionId}");
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/sessions/{$sessionId}");

            $data = $response->json();

            if ($response->successful()) {
                // Atualizar cache
                Cache::put("whatsapp_session_{$sessionId}", $data, 300);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error getting session status', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get session status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar mensagem
     */
    public function sendMessage($sessionId, $to, $message)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/api/chats/send", [
                    'sessionId' => $sessionId,
                    'to' => $this->formatPhoneNumber($to),
                    'message' => $message
                ]);

            $data = $response->json();

            if ($response->successful() && $data['success']) {
                Log::info('WhatsApp message sent', [
                    'sessionId' => $sessionId,
                    'to' => $to,
                    'messageId' => $data['messageId'] ?? null
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId,
                'to' => $to
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deletar sessão
     */
    public function deleteSession($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->delete("{$this->baseUrl}/api/sessions/{$sessionId}");

            $data = $response->json();

            if ($response->successful()) {
                // Remover do cache
                Cache::forget("whatsapp_session_{$sessionId}");
                
                Log::info('WhatsApp session deleted', [
                    'sessionId' => $sessionId
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error deleting session', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete session: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Listar todas as sessões
     */
    public function listSessions()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/sessions");

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error listing sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to list sessions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Testar conectividade com API
     */
    public function testConnection()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/test/qr");

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => $response->handlerStats()['total_time'] ?? 0,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Headers para requisições
     */
    private function getHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Laravel-WhatsApp-Client/1.0'
        ];

        if ($this->secret) {
            $headers['Authorization'] = $this->secret;
        }

        return $headers;
    }

    /**
     * Formatar número de telefone
     */
    private function formatPhoneNumber($phone)
    {
        // Remove caracteres especiais
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adiciona código do país se não tiver
        if (strlen($phone) === 11 && substr($phone, 0, 1) !== '55') {
            $phone = '55' . $phone;
        }
        
        return $phone;
    }
}
```

## 🎮 Controller de Exemplo

### `app/Http/Controllers/WhatsAppController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Dashboard principal
     */
    public function dashboard()
    {
        $sessions = $this->whatsappService->listSessions();
        $connectionTest = $this->whatsappService->testConnection();

        return view('whatsapp.dashboard', compact('sessions', 'connectionTest'));
    }

    /**
     * Criar nova sessão
     */
    public function createSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'nullable|string|max:50|alpha_dash'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $result = $this->whatsappService->createSession($request->sessionId);

        return response()->json($result);
    }

    /**
     * Status da sessão
     */
    public function sessionStatus($sessionId)
    {
        $result = $this->whatsappService->getSessionStatus($sessionId);
        return response()->json($result);
    }

    /**
     * Enviar mensagem
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'to' => 'required|string',
            'message' => 'required|string|max:4096'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $result = $this->whatsappService->sendMessage(
            $request->sessionId,
            $request->to,
            $request->message
        );

        return response()->json($result);
    }

    /**
     * Deletar sessão
     */
    public function deleteSession($sessionId)
    {
        $result = $this->whatsappService->deleteSession($sessionId);
        return response()->json($result);
    }

    /**
     * Teste de conectividade
     */
    public function testConnection()
    {
        $result = $this->whatsappService->testConnection();
        return response()->json($result);
    }
}
```

## 🛣️ Rotas

### `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

// Dashboard principal
Route::get('/', [WhatsAppController::class, 'dashboard'])->name('dashboard');

// Rotas do WhatsApp
Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/dashboard', [WhatsAppController::class, 'dashboard'])->name('dashboard');
    Route::post('/sessions', [WhatsAppController::class, 'createSession'])->name('sessions.create');
    Route::get('/sessions/{sessionId}/status', [WhatsAppController::class, 'sessionStatus'])->name('sessions.status');
    Route::delete('/sessions/{sessionId}', [WhatsAppController::class, 'deleteSession'])->name('sessions.delete');
    Route::post('/send-message', [WhatsAppController::class, 'sendMessage'])->name('send.message');
    Route::get('/test-connection', [WhatsAppController::class, 'testConnection'])->name('test.connection');
});
```

## 📊 Comandos de Deploy no Hostinger

### 1. Via cPanel File Manager

1. **Upload dos arquivos:**
   - Compacte o projeto Laravel
   - Faça upload para `public_html/`
   - Extraia os arquivos

2. **Configurar permissões:**
   ```bash
   # Via Terminal (se disponível)
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

### 2. Via SSH (se disponível)

```bash
# Navegar para o diretório
cd public_html/

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Configurar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executar migrations
php artisan migrate --force

# Limpar caches antigos
php artisan cache:clear
php artisan view:clear
```

### 3. Configuração do Database

**Via phpMyAdmin:**
```sql
-- Criar database
CREATE DATABASE u123456789_whatsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário (se necessário)
CREATE USER 'u123456789_user'@'localhost' IDENTIFIED BY 'SuaSenhaSegura123!';
GRANT ALL PRIVILEGES ON u123456789_whatsapp.* TO 'u123456789_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🔒 Configurações de Segurança

### 1. Arquivo `config/cors.php`

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://sua-api-whatsapp.vercel.app',
        'https://seudominio.com',
        'https://www.seudominio.com'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 2. Middleware de API

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateApiSecret
{
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->header('Authorization');
        $expectedSecret = config('services.whatsapp.secret');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
```

## 📋 Checklist de Deploy

### Antes do Deploy
- [ ] Testar localmente
- [ ] Configurar `.env` de produção
- [ ] Otimizar autoloader do Composer
- [ ] Configurar cache do Laravel

### Deploy no Hostinger
- [ ] Upload dos arquivos
- [ ] Configurar `.htaccess`
- [ ] Configurar permissões de pastas
- [ ] Criar database MySQL
- [ ] Executar migrations
- [ ] Configurar SSL

### Pós-Deploy
- [ ] Testar conectividade com API Vercel
- [ ] Verificar logs de erro
- [ ] Testar funcionalidades principais
- [ ] Configurar backups

**Pronto! Laravel configurado no Hostinger! 🏠**