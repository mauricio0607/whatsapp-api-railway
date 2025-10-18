<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    private $baseUrl;
    private $apiKey;
    private $timeout;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.vercel_api_url');
        $this->apiKey = config('whatsapp.api_key');
        $this->timeout = config('whatsapp.timeout', 30);
    }

    /**
     * Lista todas as sessões ativas
     */
    public function listSessions()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/api/sessions');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao listar sessões: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - listSessions: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cria uma nova sessão
     */
    public function createSession($sessionId, $autoConnect = true, $options = [])
    {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'autoConnect' => $autoConnect,
                'options' => $options
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/sessions', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao criar sessão: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - createSession: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém status de uma sessão específica
     */
    public function getSessionStatus($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/api/sessions/' . $sessionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao obter status da sessão: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - getSessionStatus: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Conecta uma sessão
     */
    public function connectSession($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/sessions/' . $sessionId, [
                    'action' => 'connect'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao conectar sessão: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - connectSession: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desconecta uma sessão
     */
    public function disconnectSession($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/sessions/' . $sessionId, [
                    'action' => 'disconnect'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao desconectar sessão: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - disconnectSession: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove uma sessão
     */
    public function deleteSession($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->delete($this->baseUrl . '/api/sessions/' . $sessionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao deletar sessão: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - deleteSession: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envia mensagem de texto
     */
    public function sendMessage($sessionId, $to, $message, $options = [])
    {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'to' => $to,
                'type' => 'text',
                'message' => $message
            ];

            if (!empty($options)) {
                $payload = array_merge($payload, $options);
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/chats/send', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - sendMessage: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envia mensagem com mídia
     */
    public function sendMediaMessage($sessionId, $to, $type, $mediaUrl, $caption = '', $options = [])
    {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'to' => $to,
                'type' => $type,
                'mediaUrl' => $mediaUrl,
                'caption' => $caption
            ];

            if (!empty($options)) {
                $payload = array_merge($payload, $options);
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/chats/send', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar mídia: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - sendMediaMessage: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envia mensagem com botões
     */
    public function sendButtonMessage($sessionId, $to, $text, $buttons, $footer = '', $options = [])
    {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'to' => $to,
                'type' => 'button',
                'text' => $text,
                'buttons' => $buttons,
                'footer' => $footer
            ];

            if (!empty($options)) {
                $payload = array_merge($payload, $options);
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/chats/send', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem com botões: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - sendButtonMessage: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gera QR Code para teste
     */
    public function generateQRCode($sessionId = null, $format = 'json')
    {
        try {
            $url = $this->baseUrl . '/api/test/qr';
            
            if ($sessionId) {
                $url .= '?sessionId=' . $sessionId;
            }
            
            if ($format !== 'json') {
                $url .= ($sessionId ? '&' : '?') . 'format=' . $format;
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao gerar QR Code: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - generateQRCode: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Testa conexão com a API
     */
    public function testConnection()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/api/sessions');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Conexão com API estabelecida com sucesso',
                    'data' => [
                        'status' => 'connected',
                        'response_time' => $response->transferStats->getTransferTime(),
                        'api_url' => $this->baseUrl
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Falha na conexão com API: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - testConnection: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém informações de uma sessão com QR Code
     */
    public function getSessionWithQR($sessionId)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/api/sessions/' . $sessionId . '?includeQR=true');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao obter sessão com QR: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp API Error - getSessionWithQR: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }
}