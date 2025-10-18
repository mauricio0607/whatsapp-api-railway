# 🚀 Guia Completo de Instalação - Deploy Híbrido WhatsApp API

## 📋 Visão Geral

Este guia fornece instruções passo a passo para implementar o deploy híbrido da WhatsApp API, onde:
- **Frontend Laravel** roda no **Hostinger**
- **API WhatsApp** roda no **Vercel** (serverless)

## 🛠️ Pré-requisitos

### Ferramentas Necessárias
- [x] Node.js 18+ instalado
- [x] NPM ou PNPM
- [x] Conta no Vercel
- [x] Conta no Hostinger
- [x] Git instalado

### Contas e Acessos
- [x] Acesso ao painel Hostinger
- [x] Acesso ao painel Vercel
- [x] Repositório Git configurado

## 📁 Estrutura do Projeto

```
projeto/
├── whatsapp-api-vercel/          # API Serverless (Vercel)
│   ├── api/
│   │   ├── sessions/
│   │   ├── chats/
│   │   └── test/
│   ├── lib/
│   ├── package.json
│   ├── vercel.json
│   └── .env.example
├── app/                          # Laravel App (Hostinger)
│   ├── Services/
│   │   └── WhatsAppService.php
│   └── Providers/
│       └── WhatsAppServiceProvider.php
└── config/
    └── whatsapp.php
```

## 🚀 Parte 1: Deploy da API no Vercel

### 1.1 Preparação do Ambiente

```bash
# Navegar para o diretório da API
cd whatsapp-api-vercel

# Instalar dependências
npm install

# Copiar arquivo de ambiente
cp .env.example .env
```

### 1.2 Configuração das Variáveis de Ambiente

Edite o arquivo `.env`:

```env
# Configurações básicas
NODE_ENV=production
WHATSAPP_API_KEY=sua-chave-super-secreta-aqui

# CORS - URL do seu Laravel no Hostinger
CORS_ORIGIN=https://seudominio.com.br

# Configurações WhatsApp
WHATSAPP_SESSION_TIMEOUT=300000
WHATSAPP_QR_TIMEOUT=60000
WHATSAPP_MAX_SESSIONS=10

# Rate Limiting
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60

# Logging
LOG_LEVEL=info
LOG_REQUESTS=true

# Webhook (opcional)
WEBHOOK_URL=https://seudominio.com.br/webhook/whatsapp
WEBHOOK_SECRET=sua-chave-webhook
```

### 1.3 Deploy no Vercel

#### Opção A: Via Script Automatizado (Recomendado)

**Windows:**
```powershell
.\deploy.ps1
```

**Linux/Mac:**
```bash
chmod +x deploy.sh
./deploy.sh
```

#### Opção B: Deploy Manual

```bash
# Instalar Vercel CLI
npm install -g vercel

# Login no Vercel
vercel login

# Deploy
vercel --prod
```

### 1.4 Configuração no Painel Vercel

1. Acesse [vercel.com](https://vercel.com)
2. Vá para seu projeto
3. Acesse **Settings > Environment Variables**
4. Adicione todas as variáveis do arquivo `.env`
5. Redeploy o projeto

### 1.5 Teste da API

```bash
# Teste básico
curl https://sua-api.vercel.app/api/test/qr

# Teste com autenticação
curl -H "X-API-Key: sua-chave-api" https://sua-api.vercel.app/api/sessions
```

## 🏠 Parte 2: Configuração do Laravel no Hostinger

### 2.1 Upload dos Arquivos

Faça upload dos seguintes arquivos para o Hostinger:

```
app/Services/WhatsAppService.php
app/Providers/WhatsAppServiceProvider.php
config/whatsapp.php
```

### 2.2 Configuração do Laravel

#### 2.2.1 Registrar o Service Provider

Edite `config/app.php`:

```php
'providers' => [
    // ... outros providers
    App\Providers\WhatsAppServiceProvider::class,
],
```

#### 2.2.2 Configurar Variáveis de Ambiente

Edite `.env` no Hostinger:

```env
# WhatsApp API Configuration
WHATSAPP_VERCEL_API_URL=https://sua-api.vercel.app
WHATSAPP_API_KEY=sua-chave-super-secreta-aqui
WHATSAPP_TIMEOUT=30
WHATSAPP_RETRY_ATTEMPTS=3
WHATSAPP_LOGGING_ENABLED=true
```

#### 2.2.3 Limpar Cache

```bash
php artisan config:cache
php artisan cache:clear
```

### 2.3 Teste da Integração

Crie um controller de teste:

```php
<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;

class WhatsAppTestController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function testConnection()
    {
        $result = $this->whatsappService->testConnection();
        return response()->json($result);
    }

    public function listSessions()
    {
        $result = $this->whatsappService->listSessions();
        return response()->json($result);
    }
}
```

## 🔧 Parte 3: Configurações Avançadas

### 3.1 Configuração de CORS

No Vercel, certifique-se de que o CORS está configurado corretamente:

```javascript
// lib/cors.js já configurado automaticamente
const allowedOrigins = [
    'https://seudominio.com.br',
    'https://www.seudominio.com.br'
];
```

### 3.2 Configuração de Webhook (Opcional)

Se quiser receber notificações em tempo real:

```php
// routes/web.php
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);
```

### 3.3 Monitoramento e Logs

#### No Vercel:
- Acesse **Functions** para ver logs em tempo real
- Configure alertas no painel

#### No Laravel:
```php
// Logs automáticos já configurados no WhatsAppService
Log::info('WhatsApp API called', ['method' => 'sendMessage']);
```

## 🧪 Parte 4: Testes e Validação

### 4.1 Testes da API

```bash
# Teste de conexão
curl -H "X-API-Key: sua-chave" https://sua-api.vercel.app/api/sessions

# Teste de QR Code
curl -H "X-API-Key: sua-chave" https://sua-api.vercel.app/api/test/qr

# Teste de criação de sessão
curl -X POST -H "X-API-Key: sua-chave" \
     -H "Content-Type: application/json" \
     -d '{"sessionId":"test123","autoConnect":true}' \
     https://sua-api.vercel.app/api/sessions
```

### 4.2 Testes do Laravel

```php
// Teste via Tinker
php artisan tinker

$service = app(App\Services\WhatsAppService::class);
$result = $service->testConnection();
dd($result);
```

### 4.3 Teste de Envio de Mensagem

```php
$result = $service->sendMessage('session123', '5511999999999', 'Teste de mensagem');
dd($result);
```

## 🚨 Solução de Problemas

### Problemas Comuns

#### 1. Erro de CORS
```
Solução: Verifique se o domínio está correto no .env do Vercel
CORS_ORIGIN=https://seudominio.com.br
```

#### 2. Erro de Autenticação
```
Solução: Verifique se a API Key é a mesma nos dois ambientes
```

#### 3. Timeout de Conexão
```
Solução: Aumente o timeout no config/whatsapp.php
'timeout' => 60,
```

#### 4. Sessão não conecta
```
Solução: Verifique os logs no Vercel e teste o QR Code
```

### Logs e Debugging

#### Vercel:
```bash
vercel logs sua-api.vercel.app
```

#### Laravel:
```bash
tail -f storage/logs/laravel.log
```

## 📊 Monitoramento

### Métricas Importantes

1. **Tempo de resposta da API**
2. **Taxa de sucesso das mensagens**
3. **Número de sessões ativas**
4. **Uso de recursos no Vercel**

### Alertas Recomendados

- API fora do ar por mais de 5 minutos
- Taxa de erro acima de 5%
- Mais de 100 requisições por minuto

## 🔒 Segurança

### Checklist de Segurança

- [x] API Key forte e única
- [x] CORS configurado corretamente
- [x] Rate limiting ativo
- [x] Logs de auditoria
- [x] HTTPS em todos os endpoints
- [x] Validação de entrada em todos os endpoints

## 📈 Otimização

### Performance

1. **Cache de sessões** - Implementado automaticamente
2. **Rate limiting** - Configurado por padrão
3. **Compressão** - Ativada no Vercel
4. **CDN** - Vercel Edge Network

### Custos

- **Vercel**: Plano gratuito suporta até 100GB de bandwidth
- **Hostinger**: Sem custos adicionais
- **Monitoramento**: Logs inclusos

## 🎯 Próximos Passos

1. **Implementar webhook** para notificações em tempo real
2. **Adicionar dashboard** para monitoramento
3. **Configurar backup** das sessões
4. **Implementar auto-scaling** se necessário

## 📞 Suporte

### Documentação
- [Documentação Vercel](https://vercel.com/docs)
- [Documentação Laravel](https://laravel.com/docs)
- [Baileys WhatsApp](https://github.com/WhiskeySockets/Baileys)

### Contato
- Abra uma issue no repositório
- Consulte os logs para debugging
- Verifique a documentação da API

---

## ✅ Checklist Final

- [ ] API deployada no Vercel
- [ ] Variáveis de ambiente configuradas
- [ ] Laravel atualizado no Hostinger
- [ ] Testes de conexão realizados
- [ ] CORS configurado
- [ ] Logs funcionando
- [ ] Backup configurado
- [ ] Monitoramento ativo

**🎉 Parabéns! Seu deploy híbrido está completo e funcionando!**