<?php

/**
 * Script de Teste para Integração WhatsApp API
 * Execute este script para testar a comunicação entre Laravel e Vercel
 */

require_once 'vendor/autoload.php';

use App\Services\WhatsAppService;

class WhatsAppIntegrationTest
{
    private $whatsappService;
    private $results = [];

    public function __construct()
    {
        // Simular ambiente Laravel
        $this->loadEnvironment();
        $this->whatsappService = new WhatsAppService();
    }

    private function loadEnvironment()
    {
        // Carregar variáveis de ambiente do .env
        if (file_exists('.env')) {
            $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }

    public function runAllTests()
    {
        echo "🧪 Iniciando testes de integração WhatsApp API...\n\n";

        $this->testConnection();
        $this->testListSessions();
        $this->testCreateSession();
        $this->testQRGeneration();
        $this->testSessionStatus();

        $this->displayResults();
    }

    private function testConnection()
    {
        echo "1️⃣ Testando conexão com API...\n";
        
        try {
            $result = $this->whatsappService->testConnection();
            
            if ($result['success']) {
                $this->results['connection'] = '✅ PASSOU';
                echo "   ✅ Conexão estabelecida com sucesso\n";
                echo "   📊 Tempo de resposta: " . ($result['data']['response_time'] ?? 'N/A') . "\n";
            } else {
                $this->results['connection'] = '❌ FALHOU';
                echo "   ❌ Falha na conexão: " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            $this->results['connection'] = '❌ ERRO';
            echo "   ❌ Erro: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function testListSessions()
    {
        echo "2️⃣ Testando listagem de sessões...\n";
        
        try {
            $result = $this->whatsappService->listSessions();
            
            if ($result['success']) {
                $this->results['list_sessions'] = '✅ PASSOU';
                echo "   ✅ Listagem de sessões funcionando\n";
                echo "   📊 Sessões encontradas: " . count($result['data']['sessions'] ?? []) . "\n";
            } else {
                $this->results['list_sessions'] = '❌ FALHOU';
                echo "   ❌ Falha na listagem: " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            $this->results['list_sessions'] = '❌ ERRO';
            echo "   ❌ Erro: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function testCreateSession()
    {
        echo "3️⃣ Testando criação de sessão...\n";
        
        $testSessionId = 'test_' . time();
        
        try {
            $result = $this->whatsappService->createSession($testSessionId, false);
            
            if ($result['success']) {
                $this->results['create_session'] = '✅ PASSOU';
                echo "   ✅ Sessão criada com sucesso\n";
                echo "   📱 Session ID: " . $testSessionId . "\n";
                
                // Limpar sessão de teste
                $this->whatsappService->deleteSession($testSessionId);
            } else {
                $this->results['create_session'] = '❌ FALHOU';
                echo "   ❌ Falha na criação: " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            $this->results['create_session'] = '❌ ERRO';
            echo "   ❌ Erro: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function testQRGeneration()
    {
        echo "4️⃣ Testando geração de QR Code...\n";
        
        try {
            $result = $this->whatsappService->generateQRCode();
            
            if ($result['success']) {
                $this->results['qr_generation'] = '✅ PASSOU';
                echo "   ✅ QR Code gerado com sucesso\n";
                echo "   🔗 Endpoint funcionando\n";
            } else {
                $this->results['qr_generation'] = '❌ FALHOU';
                echo "   ❌ Falha na geração: " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            $this->results['qr_generation'] = '❌ ERRO';
            echo "   ❌ Erro: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function testSessionStatus()
    {
        echo "5️⃣ Testando status de sessão...\n";
        
        try {
            $result = $this->whatsappService->getSessionStatus('test_session');
            
            // Esperamos que falhe pois a sessão não existe, mas a API deve responder
            if (isset($result['status']) && $result['status'] === 404) {
                $this->results['session_status'] = '✅ PASSOU';
                echo "   ✅ Endpoint de status funcionando (sessão não encontrada - esperado)\n";
            } elseif ($result['success']) {
                $this->results['session_status'] = '✅ PASSOU';
                echo "   ✅ Status de sessão funcionando\n";
            } else {
                $this->results['session_status'] = '❌ FALHOU';
                echo "   ❌ Falha no status: " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            $this->results['session_status'] = '❌ ERRO';
            echo "   ❌ Erro: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function displayResults()
    {
        echo "📊 RESUMO DOS TESTES\n";
        echo "==================\n\n";
        
        foreach ($this->results as $test => $result) {
            $testName = str_replace('_', ' ', ucwords($test));
            echo sprintf("%-20s: %s\n", $testName, $result);
        }
        
        $passed = count(array_filter($this->results, function($r) { return strpos($r, '✅') === 0; }));
        $total = count($this->results);
        
        echo "\n";
        echo "📈 RESULTADO FINAL: {$passed}/{$total} testes passaram\n";
        
        if ($passed === $total) {
            echo "🎉 Todos os testes passaram! Integração funcionando perfeitamente.\n";
        } elseif ($passed > 0) {
            echo "⚠️  Alguns testes falharam. Verifique a configuração.\n";
        } else {
            echo "❌ Todos os testes falharam. Verifique a conectividade e configuração.\n";
        }
        
        echo "\n";
        echo "🔧 PRÓXIMOS PASSOS:\n";
        echo "1. Se todos os testes passaram, sua integração está pronta!\n";
        echo "2. Se alguns falharam, verifique:\n";
        echo "   - URL da API no .env (WHATSAPP_VERCEL_API_URL)\n";
        echo "   - Chave da API (WHATSAPP_API_KEY)\n";
        echo "   - Conectividade de rede\n";
        echo "   - Logs do Vercel\n";
        echo "3. Execute este teste novamente após correções\n";
    }
}

// Executar testes se o script for chamado diretamente
if (php_sapi_name() === 'cli') {
    $tester = new WhatsAppIntegrationTest();
    $tester->runAllTests();
}

?>