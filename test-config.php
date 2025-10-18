<?php

// Teste de configuração do WhatsApp
echo "🔍 TESTE DE CONFIGURAÇÃO WHATSAPP\n";
echo "================================\n\n";

// Carregar o Laravel
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📋 Configurações atuais:\n";
echo "------------------------\n";

$configs = [
    'WHATSAPP_VERCEL_API_URL' => env('WHATSAPP_VERCEL_API_URL'),
    'WA_SERVER_URL' => env('WA_SERVER_URL'),
    'CLIENT_SERVER_URL' => env('CLIENT_SERVER_URL'),
    'APP_URL' => env('APP_URL'),
    'APP_URL_WITHOUT_SSL' => env('APP_URL_WITHOUT_SSL'),
];

foreach ($configs as $key => $value) {
    echo sprintf("%-25s: %s\n", $key, $value ?: 'NÃO DEFINIDO');
}

echo "\n📡 Teste de conexão com a API:\n";
echo "------------------------------\n";

try {
    $url = config('whatsapp.vercel_api_url');
    echo "URL configurada: $url\n";
    
    if (strpos($url, '`') !== false) {
        echo "⚠️  PROBLEMA DETECTADO: URL contém crases (`)\n";
        $url = str_replace('`', '', $url);
        echo "URL corrigida: $url\n";
    }
    
    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url . '/api');
    
    if ($response->successful()) {
        echo "✅ API respondeu com sucesso!\n";
        echo "Status: " . $response->status() . "\n";
        $data = $response->json();
        echo "Resposta: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ API retornou erro: " . $response->status() . "\n";
        echo "Corpo da resposta: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "💥 Erro de conexão: " . $e->getMessage() . "\n";
}

echo "\n🧪 Teste de QR Code:\n";
echo "-------------------\n";

try {
    $whatsappService = new \App\Services\WhatsAppService();
    $result = $whatsappService->generateQRCode();
    
    if ($result['success']) {
        echo "✅ QR Code gerado com sucesso!\n";
        echo "Session ID: " . ($result['data']['data']['sessionId'] ?? 'N/A') . "\n";
        echo "QR Code: " . (isset($result['data']['data']['qrCode']) ? 'Presente' : 'Ausente') . "\n";
    } else {
        echo "❌ Erro ao gerar QR Code: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "💥 Erro no serviço: " . $e->getMessage() . "\n";
}

echo "\n✅ Teste concluído!\n";