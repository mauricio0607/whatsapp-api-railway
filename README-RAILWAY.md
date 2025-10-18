# 🚀 WhatsApp API - Deploy no Railway

Esta é uma API WhatsApp usando Baileys, otimizada para deploy no Railway.

## 📋 Pré-requisitos

- Conta no Railway (https://railway.app)
- Git instalado
- Node.js 18+ (para desenvolvimento local)

## 🚀 Deploy no Railway

### Passo 1: Preparar o Repositório

1. Faça commit de todas as alterações:
```bash
git add .
git commit -m "Preparar para deploy no Railway"
git push origin main
```

### Passo 2: Criar Projeto no Railway

1. Acesse https://railway.app
2. Clique em "New Project"
3. Selecione "Deploy from GitHub repo"
4. Escolha seu repositório
5. Railway detectará automaticamente que é um projeto Node.js

### Passo 3: Configurar Variáveis de Ambiente

No painel do Railway, vá em **Variables** e adicione:

```env
NODE_ENV=production
WA_SERVER_HOST=0.0.0.0
WA_SERVER_PORT=8000
WA_SERVER_MAX_RETRIES=5
WA_SERVER_RECONNECT_INTERVAL=5000
SESSION_TIMEOUT=300000
QR_TIMEOUT=60000
MAX_SESSIONS=10
CORS_ORIGIN=*
LOG_LEVEL=info
DEBUG=false
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60
CACHE_ENABLED=true
CACHE_TTL=3600
```

### Passo 4: Deploy Automático

O Railway fará o deploy automaticamente. Aguarde alguns minutos.

### Passo 5: Obter URL da API

Após o deploy, você receberá uma URL como:
`https://seu-projeto.up.railway.app`

## 🔧 Configurar Laravel

Atualize o arquivo `.env` do seu Laravel:

```env
WA_SERVER_URL=https://seu-projeto.up.railway.app
WA_SERVER_HOST=seu-projeto.up.railway.app
WA_SERVER_PORT=443
```

## 🧪 Testar a API

### Teste de Conectividade
```bash
curl https://seu-projeto.up.railway.app/health
```

### Gerar QR Code
```bash
curl https://seu-projeto.up.railway.app/test/qr
```

### Verificar Status
```bash
curl https://seu-projeto.up.railway.app/test/status/test123
```

## 📊 Monitoramento

O Railway oferece:
- ✅ Logs em tempo real
- ✅ Métricas de CPU/RAM
- ✅ Deploy automático via Git
- ✅ SSL automático
- ✅ Domínio personalizado (opcional)

## 🔄 Atualizações

Para atualizar a API:
1. Faça suas alterações
2. Commit e push para o repositório
3. Railway fará o deploy automaticamente

## 🆘 Troubleshooting

### API não responde
- Verifique os logs no painel do Railway
- Confirme se as variáveis de ambiente estão corretas

### Erro de conexão WhatsApp
- Verifique se o QR code foi escaneado
- Confirme se a sessão não expirou

### Timeout no Laravel
- Aumente o timeout nas configurações do Laravel
- Verifique se a URL da API está correta

## 📞 Suporte

- Railway Docs: https://docs.railway.app
- Baileys GitHub: https://github.com/WhiskeySockets/Baileys