<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppTestController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Testa a conexão com a API Vercel
     */
    public function testConnection()
    {
        try {
            Log::info('Testando conexão com API Vercel WhatsApp');
            
            $result = $this->whatsappService->testConnection();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Teste de conexão realizado',
                'data' => $result,
                'vercel_url' => config('whatsapp.vercel_api_url'),
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no teste de conexão: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao testar conexão: ' . $e->getMessage(),
                'vercel_url' => config('whatsapp.vercel_api_url'),
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Lista todas as sessões
     */
    public function listSessions()
    {
        try {
            Log::info('Listando sessões da API Vercel');
            
            $result = $this->whatsappService->listSessions();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sessões listadas com sucesso',
                'data' => $result,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar sessões: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao listar sessões: ' . $e->getMessage(),
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Cria uma sessão de teste
     */
    public function createTestSession(Request $request)
    {
        try {
            $sessionId = $request->input('sessionId', 'laravel-test-' . time());
            
            Log::info('Criando sessão de teste: ' . $sessionId);
            
            $result = $this->whatsappService->createSession($sessionId, true);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sessão de teste criada',
                'sessionId' => $sessionId,
                'data' => $result,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar sessão de teste: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar sessão: ' . $e->getMessage(),
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Testa o endpoint de QR code
     */
    public function testQrCode()
    {
        try {
            Log::info('Testando endpoint de QR code');
            
            // Fazer requisição direta para o endpoint de teste
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->get(config('whatsapp.vercel_api_url') . '/api/test/qr');

            if ($response->successful()) {
                $apiData = $response->json();
                
                // Log da resposta da API para debug
                Log::info('Resposta da API Vercel:', $apiData);
                
                // Verificar se a API retornou sucesso e tem QR code
                if (isset($apiData['success']) && $apiData['success'] && isset($apiData['data']['qrCode'])) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'QR Code obtido com sucesso',
                        'data' => [
                            'data' => $apiData['data']  // Estrutura correta para o frontend
                        ],
                        'timestamp' => now()
                    ]);
                } else {
                    Log::error('API Vercel não retornou QR code válido:', $apiData);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'API não retornou QR code válido',
                        'api_response' => $apiData,
                        'timestamp' => now()
                    ], 500);
                }
            } else {
                Log::error('Erro HTTP da API Vercel: ' . $response->status() . ' - ' . $response->body());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao obter QR Code: ' . $response->body(),
                    'status_code' => $response->status(),
                    'timestamp' => now()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Erro ao testar QR code: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao testar QR code: ' . $e->getMessage(),
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Página de teste com interface
     */
    public function testPage()
    {
        return view('whatsapp-test', [
            'vercel_url' => config('whatsapp.vercel_api_url'),
            'api_key' => config('whatsapp.api_key') ? 'Configurada' : 'Não configurada'
        ]);
    }
}