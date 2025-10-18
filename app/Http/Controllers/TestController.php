<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    public function testWhatsAppAPI()
    {
        try {
            $waServerUrl = env('WA_SERVER_URL', 'http://127.0.0.1:8000');
            
            // Teste 1: Verificar status do servidor
            $serverStatus = Http::get($waServerUrl . '/sessions/server-status');
            
            // Teste 2: Listar sessões (se houver)
            $sessions = [];
            try {
                $sessionResponse = Http::get($waServerUrl . '/sessions/status/minha-sessao');
                $sessions['minha-sessao'] = $sessionResponse->json();
            } catch (\Exception $e) {
                $sessions['minha-sessao'] = ['error' => $e->getMessage()];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Teste de comunicação com API WhatsApp',
                'data' => [
                    'wa_server_url' => $waServerUrl,
                    'server_status' => $serverStatus->json(),
                    'sessions' => $sessions,
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na comunicação com API WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function createTestSession(Request $request)
    {
        try {
            $waServerUrl = env('WA_SERVER_URL', 'http://127.0.0.1:8000');
            $sessionId = $request->input('session_id', 'test-' . time());
            
            $response = Http::post($waServerUrl . '/sessions/add', [
                'id' => $sessionId,
                'isLegacy' => 'false'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sessão criada via Laravel',
                'data' => [
                    'session_id' => $sessionId,
                    'response' => $response->json()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar sessão',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}