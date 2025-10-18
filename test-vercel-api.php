<?php
require_once 'vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🚀 TESTE DA API VERCEL\n";
echo "====================\n\n";

$vercelUrl = $_ENV['WHATSAPP_VERCEL_API_URL'] ?? null;
echo "📍 URL da API: " . ($vercelUrl ?: 'NÃO DEFINIDA') . "\n\n";

if (!$vercelUrl) {
    echo "❌ ERRO: Variável WHATSAPP_VERCEL_API_URL não está definida no .env\n";
    exit(1);
}

// Função para fazer requisição HTTP
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WhatsApp-Test/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Teste 1: Verificar se a API está online
echo "🔍 TESTE 1: Verificando se a API está online\n";
echo "URL: $vercelUrl\n";
$result = makeRequest($vercelUrl);
echo "Status HTTP: " . $result['http_code'] . "\n";
if ($result['error']) {
    echo "❌ Erro cURL: " . $result['error'] . "\n";
} else {
    echo "✅ Conexão estabelecida\n";
}
echo "Resposta: " . substr($result['response'], 0, 200) . "...\n\n";

// Teste 2: Testar endpoint de QR Code
echo "🔍 TESTE 2: Testando endpoint /api/qr-code\n";
$qrUrl = $