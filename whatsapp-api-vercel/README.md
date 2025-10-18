# WhatsApp API Vercel

API serverless do WhatsApp usando Baileys, otimizada para deploy no Vercel e integração com Laravel no Hostinger.

## 🚀 Características

- ✅ **Serverless**: Otimizado para Vercel
- ✅ **Baileys**: Biblioteca oficial do WhatsApp Web
- ✅ **TypeScript/ESM**: Código moderno e tipado
- ✅ **CORS**: Configurado para integração com Laravel
- ✅ **Rate Limiting**: Proteção contra spam
- ✅ **Autenticação**: API Key para segurança
- ✅ **Multi-sessão**: Suporte a múltiplas sessões
- ✅ **QR Code**: Geração automática para conexão
- ✅ **Webhooks**: Notificações em tempo real

## 📁 Estrutura do Projeto

```
whatsapp-api-vercel/
├── api/
│   ├── sessions/
│   │   ├── index.js          # CRUD de sessões
│   │   └── [sessionId].js    # Operações por sessão
│   ├── chats/
│   │   └── send.js           # Envio de mensagens
│   └── test/
│       └── qr.js             # Teste de QR code
├── lib/
│   ├── whatsapp.js           # Manager principal
│   └── cors.js               # Configurações CORS
├── package.json
├── vercel.json
├── .env.example
└── README.md
```

## 🛠️ Instalação

### 1. Clone e Configure

```bash
# Clone o projeto
git clone <repository-url>
cd whatsapp-api-vercel

# Instale as dependências
npm install

# Configure as variáveis de ambiente
cp .env.example .env
```

### 2. Configure as Variáveis de Ambiente

Edite o arquivo `.env` com suas configurações:

```env
# Obrigatórias
API_KEY=your-secure-api-key-here
ALLOWED_ORIGINS=https://yourdomain.com
LARAVEL_BASE_URL=https://yourdomain.com

# Opcionais
SESSION_TIMEOUT=300000
MAX_SESSIONS=10
RATE_LIMIT_MAX=100
```

### 3. Desenvolvimento Local

```bash
# Instalar Vercel CLI
npm install -g vercel

# Executar em modo desenvolvimento
vercel dev

# Ou usar npm
npm run dev
```

### 4. Deploy para Vercel

```bash
# Login no Vercel
vercel login

# Deploy de preview
vercel

# Deploy de produção
vercel --prod
```

## 📚 Endpoints da API

### Sessões

#### Listar Sessões
```http
GET /api/sessions
```

#### Criar Sessão
```http
POST /api/sessions
Content-Type: application/json
X-API-Key: your-api-key

{
  "sessionId": "my-session",
  "autoConnect": true
}
```

#### Status da Sessão
```http
GET /api/sessions/{sessionId}
```

#### Deletar Sessão
```http
DELETE /api/sessions/{sessionId}
```

### Mensagens

#### Enviar Mensagem de Texto
```http
POST /api/chats/send
Content-Type: application/json
X-API-Key: your-api-key

{
  "sessionId": "my-session",
  "to": "5511999999999",
  "message": "Olá! Como você está?",
  "type": "text"
}
```

#### Enviar Imagem
```http
POST /api/chats/send
Content-Type: application/json
X-API-Key: your-api-key

{
  "sessionId": "my-session",
  "to": "5511999999999",
  "message": "https://example.com/image.jpg",
  "type": "image",
  "options": {
    "caption": "Legenda da imagem"
  }
}
```

### Teste

#### QR Code (HTML)
```http
GET /api/test/qr?format=html
```

#### QR Code (JSON)
```http
GET /api/test/qr?format=json
```

## 🔧 Configuração no Vercel

### 1. Variáveis de Ambiente

No dashboard do Vercel, configure:

- `API_KEY`: Chave de autenticação
- `ALLOWED_ORIGINS`: Domínios permitidos
- `LARAVEL_BASE_URL`: URL do Laravel
- `SESSION_TIMEOUT`: Timeout das sessões
- `MAX_SESSIONS`: Máximo de sessões

### 2. Domínio Personalizado

1. Acesse Project Settings
2. Vá em Domains
3. Adicione seu domínio
4. Configure DNS conforme instruções

## 🔗 Integração com Laravel

### 1. Atualizar WhatsAppService.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.vercel_api_url');
        $this->apiKey = config('whatsapp.api_key');
    }

    public function createSession($sessionId = null)
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/api/sessions', [
            'sessionId' => $sessionId,
            'autoConnect' => true
        ]);

        return $response->json();
    }

    public function sendMessage($sessionId, $to, $message, $type = 'text')
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/api/chats/send', [
            'sessionId' => $sessionId,
            'to' => $to,
            'message' => $message,
            'type' => $type
        ]);

        return $response->json();
    }
}
```

### 2. Configurar config/whatsapp.php

```php
<?php

return [
    'vercel_api_url' => env('WHATSAPP_VERCEL_API_URL', 'https://your-vercel-app.vercel.app'),
    'api_key' => env('WHATSAPP_API_KEY', 'your-api-key'),
    'default_session' => env('WHATSAPP_DEFAULT_SESSION', 'default'),
];
```

## 🔒 Segurança

### Autenticação

Todas as requisições devem incluir o header:
```
X-API-Key: your-api-key
```

### CORS

Configure `ALLOWED_ORIGINS` com os domínios permitidos:
```env
ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

### Rate Limiting

- 100 requisições por minuto por IP
- 10 mensagens por minuto por destinatário
- Configurável via variáveis de ambiente

## 📊 Monitoramento

### Health Check
```http
GET /api/health
```

### Logs

Os logs são automaticamente enviados para o Vercel. Para desenvolvimento:

```bash
vercel logs
```

## 🐛 Troubleshooting

### Erro: Session not found
- Verifique se a sessão foi criada
- Confirme o sessionId correto

### Erro: QR code expired
- Gere um novo QR code
- Verifique o timeout configurado

### Erro: CORS
- Confirme ALLOWED_ORIGINS
- Verifique headers da requisição

### Erro: Rate limit exceeded
- Aguarde o reset do limite
- Ajuste RATE_LIMIT_MAX se necessário

## 📝 Exemplos de Uso

### JavaScript/Fetch
```javascript
// Criar sessão
const response = await fetch('https://your-api.vercel.app/api/sessions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your-api-key'
  },
  body: JSON.stringify({
    sessionId: 'test-session'
  })
});

const session = await response.json();
console.log('QR Code:', session.qrCode);
```

### PHP/Laravel
```php
// Enviar mensagem
$whatsapp = new WhatsAppService();
$result = $whatsapp->sendMessage(
    'test-session',
    '5511999999999',
    'Olá do Laravel!'
);
```

### cURL
```bash
# Criar sessão
curl -X POST https://your-api.vercel.app/api/sessions \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{"sessionId": "test"}'

# Enviar mensagem
curl -X POST https://your-api.vercel.app/api/chats/send \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "sessionId": "test",
    "to": "5511999999999",
    "message": "Olá!",
    "type": "text"
  }'
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para detalhes.

## 🆘 Suporte

- 📧 Email: support@yourdomain.com
- 💬 Discord: [Link do Discord]
- 📖 Documentação: [Link da Documentação]

---

**Desenvolvido com ❤️ para integração WhatsApp + Laravel + Vercel**