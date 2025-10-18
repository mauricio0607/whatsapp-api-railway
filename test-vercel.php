<?php
/**
 * Teste de Conectividade com API Vercel
 * Arquivo para testar se a API WhatsApp Vercel está funcionando
 */

// Carregar variáveis de ambiente
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        if (strpos($line, 'WHATSAPP_VERCEL_API_URL=') === 0) {
            $apiUrl = trim(str_replace('WHATSAPP_VERCEL_API_URL=', '', $line));
            break;
        }
    }
}

if (!isset($apiUrl) || empty($apiUrl)) {
    die("❌ ERRO: Variável WHATSAPP_VERCEL_API_URL não encontrada no .env\n");
}

echo "🚀 TESTE DE CONECTIVIDADE COM API VERCEL\n";
echo "==========================================\n";
echo "📍 URL da API: $apiUrl\n\n";

/**
 * Função para fazer requisição HTTP com timeout
 */
function makeRequest($url, $timeout = 10) {
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WhatsApp-Test/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    return [
        'success' => $response !== false,
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error,
        'response_time' => $responseTime
    ];
}

// Teste 1: Verificar se a URL base está acessível
echo "🔍 TESTE 1: Verificando URL base da API\n";
echo "----------------------------------------\n";
$result1 = makeRequest($apiUrl);

if ($result1['success']) {
    echo "✅ Status: Sucesso\n";
    echo "📊 Código HTTP: {$result1['http_code']}\n";
    echo "⏱️ Tempo de resposta: {$result1['response_time']}ms\n";
    echo "📄 Resposta: " . substr($result1['response'], 0, 200) . "...\n\n";
} else {
    echo "❌ Status: Falha\n";
    echo "🚫 Erro: {$result1['error']}\n";
    echo "⏱️ Tempo de resposta: {$result1['response_time']}ms\n\n";
}

// Teste 2: Testar endpoint de QR Code
echo "🔍 TESTE 2: Testando endpoint /api/test/qr\n";
echo "-------------------------------------------\n";
$qrUrl = $apiUrl . '/api/test/qr?sessionId=device_1';
echo "📍 URL: $qrUrl\n";

$result2 = makeRequest($qrUrl, 15); // Timeout maior para QR

if ($result2['success']) {
    echo "✅ Status: Sucesso\n";
    echo "📊 Código HTTP: {$result2['http_code']}\n";
    echo "⏱️ Tempo de resposta: {$result2['response_time']}ms\n";
    
    // Tentar decodificar JSON
    $jsonData = json_decode($result2['response'], true);
    if ($jsonData) {
        echo "📄 Resposta JSON:\n";
        echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } else {
        echo "📄 Resposta (não é JSON válido):\n";
        echo substr($result2['response'], 0, 500) . "...\n\n";
    }
} else {
    echo "❌ Status: Falha\n";
    echo "🚫 Erro: {$result2['error']}\n";
    echo "⏱️ Tempo de resposta: {$result2['response_time']}ms\n\n";
}

// Teste 3: Testar endpoint de sessões
echo "🔍 TESTE 3: Testando endpoint /api/sessions\n";
echo "-------------------------------------------\n";
$sessionsUrl = $apiUrl . '/api/sessions/device_1';
echo "📍 URL: $sessionsUrl\n";

$result3 = makeRequest($sessionsUrl);

if ($result3['success']) {
    echo "✅ Status: Sucesso\n";
    echo "📊 Código HTTP: {$result3['http_code']}\n";
    echo "⏱️ Tempo de resposta: {$result3['response_time']}ms\n";
    
    // Tentar decodificar JSON
    $jsonData = json_decode($result3['response'], true);
    if ($jsonData) {
        echo "📄 Resposta JSON:\n";
        echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } else {
        echo "📄 Resposta (não é JSON válido):\n";
        echo substr($result3['response'], 0, 500) . "...\n\n";
    }
} else {
    echo "❌ Status: Falha\n";
    echo "🚫 Erro: {$result3['error']}\n";
    echo "⏱️ Tempo de resposta: {$result3['response_time']}ms\n\n";
}

// Resumo dos testes
echo "📋 RESUMO DOS TESTES\n";
echo "====================\n";
echo "🌐 URL Base: " . ($result1['success'] ? "✅ OK ({$result1['response_time']}ms)" : "❌ FALHA") . "\n";
echo "🔲 QR Code: " . ($result2['success'] ? "✅ OK ({$result2['response_time']}ms)" : "❌ FALHA") . "\n";
echo "📊 Sessões: " . ($result3['success'] ? "✅ OK ({$result3['response_time']}ms)" : "❌ FALHA") . "\n";

$totalTests = 3;
$passedTests = ($result1['success'] ? 1 : 0) + ($result2['success'] ? 1 : 0) + ($result3['success'] ? 1 : 0);

echo "\n🎯 RESULTADO FINAL: $passedTests/$totalTests testes passaram\n";

if ($passedTests === $totalTests) {
    echo "🎉 TODOS OS TESTES PASSARAM! A API Vercel está funcionando.\n";
} else {
    echo "⚠️ ALGUNS TESTES FALHARAM. Verifique a conectividade com a API Vercel.\n";
}

echo "\n💡 DICAS:\n";
echo "- Se os testes falharam, verifique se a URL da API está correta no .env\n";
echo "- Verifique sua conexão com a internet\n";
echo "- A API Vercel pode estar temporariamente indisponível\n";
echo "- Tempos de resposta acima de 5000ms podem causar timeout no frontend\n";
?>