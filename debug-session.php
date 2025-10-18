<?php

/**
 * Debug Script - Verificar Status da Sessão WhatsApp
 * 
 * Este script ajuda a diagnosticar por que o status continua inactive
 * mesmo após a conexão do WhatsApp ter sido estabelecida.
 */

require_once 'vendor/autoload.php';

// Carregar configurações do Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "🔍 DEBUG - Status da Sessão WhatsApp\n";
echo "=====================================\n\n";

// 1. Buscar device no banco de dados
echo "1️⃣ Buscando device no banco de dados...\n";
$deviceUuid = 'eada0281-7331-4501-ad79-12a6f7bd9b2b';

try {
    $device = DB::table('devices')->where('uuid', $deviceUuid)->first();
    
    if ($device) {
        echo "✅ Device encontrado!\n";
        echo "   - ID: {$device->id}\n";
        echo "   - UUID: {$device->uuid}\n";
        echo "   - Nome: {$device->name}\n";
        echo "   - Status atual: " . ($device->status ? 'Ativo' : 'Inativo') . "\n";
        echo "   - Telefone: " . ($device->phone ?? 'Não definido') . "\n";
        echo "   - User ID: {$device->user_id}\n";
        echo "   - Session ID salvo: " . ($device->session_id ?? 'Não definido') . "\n";
        echo "   - Criado em: {$device->created_at}\n\n";
        
        $sessionId = $device->session_id ?? 'device_' . $device->id;
        echo "📱 Session ID a ser usado: {$sessionId}\n\n";
        
    } else {
        echo "❌ Device não encontrado no banco de dados!\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar device: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Testar API local - Listar todas as sessões
echo "2️⃣ Listando todas as sessões ativas na API local...\n";
try {
    $response = Http::timeout(10)->get('http://127.0.0.1:8000/sessions');
    
    echo "Status HTTP: " . $response->status() . "\n";
    echo "Resposta completa:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if ($response->successful()) {
        $sessions = $response->json();
        if (isset($sessions['data']) && is_array($sessions['data'])) {
            echo "📋 Sessões encontradas: " . count($sessions['data']) . "\n";
            foreach ($sessions['data'] as $session) {
                echo "   - Session ID: " . ($session['sessionId'] ?? 'N/A') . "\n";
                echo "     Status: " . ($session['status'] ?? 'N/A') . "\n";
                echo "     Conectado: " . (isset($session['isConnected']) ? ($session['isConnected'] ? 'Sim' : 'Não') : 'N/A') . "\n";
                echo "     ---\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao listar sessões: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Testar status da sessão específica
echo "3️⃣ Verificando status da sessão específica...\n";
try {
    $apiUrl = env('WA_SERVER_URL', 'http://127.0.0.1:8000');
    $statusUrl = "{$apiUrl}/test/status/{$sessionId}";
    echo "URL de teste: {$statusUrl}\n";
    
    $response = Http::timeout(10)->get($statusUrl);
    
    echo "Status HTTP: " . $response->status() . "\n";
    echo "Resposta completa:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if ($response->successful()) {
        $sessionData = $response->json();
        
        if (isset($sessionData['success']) && $sessionData['success']) {
            echo "✅ API respondeu com sucesso!\n";
            
            if (isset($sessionData['data'])) {
                $data = $sessionData['data'];
                echo "📊 Dados da sessão:\n";
                echo "   - Valid Session: " . (isset($data['valid_session']) ? ($data['valid_session'] ? 'true' : 'false') : 'N/A') . "\n";
                echo "   - Is Connected: " . (isset($data['isConnected']) ? ($data['isConnected'] ? 'true' : 'false') : 'N/A') . "\n";
                echo "   - Status: " . ($data['status'] ?? 'N/A') . "\n";
                
                if (isset($data['user'])) {
                    echo "   - Usuário conectado: " . ($data['user']['name'] ?? 'N/A') . "\n";
                    echo "   - ID do usuário: " . ($data['user']['id'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "⚠️ API respondeu, mas sem sucesso\n";
            echo "Mensagem: " . ($sessionData['message'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Erro na API (Status: " . $response->status() . ")\n";
        echo "Resposta: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar status: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Verificar endpoints disponíveis na API local
echo "4️⃣ Testando endpoint base da API local...\n";
try {
    $response = Http::timeout(10)->get('http://127.0.0.1:8000/');
    
    echo "Status HTTP: " . $response->status() . "\n";
    if ($response->successful()) {
        echo "✅ API local está respondendo!\n";
        $data = $response->json();
        if (isset($data['available_endpoints'])) {
            echo "📋 Endpoints disponíveis:\n";
            foreach ($data['available_endpoints'] as $endpoint) {
                echo "   - {$endpoint}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao testar API base: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Verificar configurações do .env
echo "5️⃣ Verificando configurações do .env...\n";
echo "WA_SERVER_URL: " . env('WA_SERVER_URL') . "\n";
echo "WA_SERVER_HOST: " . env('WA_SERVER_HOST') . "\n";
echo "WA_SERVER_PORT: " . env('WA_SERVER_PORT') . "\n";

echo "\n";

// 6. Sugestões de solução
echo "💡 ANÁLISE E SUGESTÕES:\n";
echo "========================\n";
echo "1. Verifique se o Session ID está correto: {$sessionId}\n";
echo "2. Confirme se a API local está usando o mesmo formato de resposta\n";
echo "3. Verifique se o campo 'valid_session' ou 'isConnected' está sendo retornado\n";
echo "4. Confirme se a sessão foi criada com o ID correto na API local\n";
echo "5. Verifique se há diferença entre os endpoints da API local e Vercel\n\n";

echo "🔧 PRÓXIMOS PASSOS:\n";
echo "===================\n";
echo "1. Se a sessão não existir, criar uma nova com o ID correto\n";
echo "2. Ajustar o DeviceController.php para usar o formato correto da resposta\n";
echo "3. Verificar se os campos de verificação estão corretos\n";
echo "4. Testar manualmente a conexão do WhatsApp\n\n";

echo "✅ Debug concluído!\n";