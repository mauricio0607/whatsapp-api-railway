<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para comunicação com a API do WhatsApp no Vercel
    |
    */

    // URL da API do WhatsApp no Vercel
    'vercel_api_url' => env('WHATSAPP_VERCEL_API_URL', 'https://your-vercel-app.vercel.app'),

    // Chave de API para autenticação
    'api_key' => env('WHATSAPP_API_KEY', 'your-secure-api-key'),

    // Timeout para requisições HTTP (em segundos)
    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    // Configurações de retry
    'retry' => [
        'attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
        'delay' => env('WHATSAPP_RETRY_DELAY', 1000), // em milissegundos
    ],

    // Configurações de rate limiting
    'rate_limit' => [
        'enabled' => env('WHATSAPP_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('WHATSAPP_RATE_LIMIT_MAX', 100),
        'window' => env('WHATSAPP_RATE_LIMIT_WINDOW', 60), // em segundos
    ],

    // Configurações de logging
    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', true),
        'level' => env('WHATSAPP_LOG_LEVEL', 'info'),
        'channel' => env('WHATSAPP_LOG_CHANNEL', 'single'),
    ],

    // Configurações de sessão
    'session' => [
        'auto_connect' => env('WHATSAPP_AUTO_CONNECT', true),
        'qr_timeout' => env('WHATSAPP_QR_TIMEOUT', 60), // em segundos
        'reconnect_attempts' => env('WHATSAPP_RECONNECT_ATTEMPTS', 5),
    ],

    // Configurações de mensagens
    'messages' => [
        'delay' => env('WHATSAPP_MESSAGE_DELAY', 1000), // em milissegundos
        'max_retries' => env('WHATSAPP_MESSAGE_MAX_RETRIES', 3),
        'chunk_size' => env('WHATSAPP_MESSAGE_CHUNK_SIZE', 50),
    ],

    // Configurações de mídia
    'media' => [
        'max_size' => env('WHATSAPP_MEDIA_MAX_SIZE', 16777216), // 16MB em bytes
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'avi', 'mov', 'wmv'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        ],
        'upload_path' => env('WHATSAPP_MEDIA_UPLOAD_PATH', 'uploads/whatsapp'),
    ],

    // Configurações de webhook
    'webhook' => [
        'enabled' => env('WHATSAPP_WEBHOOK_ENABLED', false),
        'url' => env('WHATSAPP_WEBHOOK_URL'),
        'secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        'events' => [
            'message.received',
            'session.connected',
            'session.disconnected',
            'qr.generated',
        ],
    ],

    // Configurações de cache
    'cache' => [
        'enabled' => env('WHATSAPP_CACHE_ENABLED', true),
        'ttl' => env('WHATSAPP_CACHE_TTL', 3600), // em segundos
        'prefix' => env('WHATSAPP_CACHE_PREFIX', 'whatsapp:'),
    ],

    // Configurações de segurança
    'security' => [
        'cors_origins' => env('WHATSAPP_CORS_ORIGINS', '*'),
        'allowed_ips' => env('WHATSAPP_ALLOWED_IPS', ''),
        'encryption_key' => env('WHATSAPP_ENCRYPTION_KEY'),
    ],

    // Configurações de desenvolvimento
    'development' => [
        'debug' => env('WHATSAPP_DEBUG', false),
        'mock_responses' => env('WHATSAPP_MOCK_RESPONSES', false),
        'test_mode' => env('WHATSAPP_TEST_MODE', false),
    ],
];