# 🚀 Guia Completo: GitHub + Railway Deploy

## 📋 Passo a Passo Completo

### 🔧 Passo 1: Preparar o Projeto Localmente

**1.1 Inicializar Git (se ainda não foi feito):**
```bash
git init
```

**1.2 Configurar Git (primeira vez):**
```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu.email@gmail.com"
```

**1.3 Adicionar arquivos:**
```bash
git add .
```

**1.4 Fazer primeiro commit:**
```bash
git commit -m "🚀 Initial commit: WhatsApp API ready for Railway"
```

### 🌐 Passo 2: Criar Repositório no GitHub

**2.1 Acesse:** https://github.com

**2.2 Clique em "New repository" (botão verde)**

**2.3 Configure o repositório:**
- **Repository name:** `whatsapp-api-railway`
- **Description:** `WhatsApp API com Baileys para Railway`
- **Visibility:** Private (recomendado) ou Public
- ❌ **NÃO** marque "Add a README file"
- ❌ **NÃO** marque "Add .gitignore"
- ❌ **NÃO** marque "Choose a license"

**2.4 Clique em "Create repository"**

### 🔗 Passo 3: Conectar Local com GitHub

**3.1 Copie a URL do seu repositório:**
```
https://github.com/SEU_USUARIO/whatsapp-api-railway.git
```

**3.2 Adicione o remote origin:**
```bash
git remote add origin https://github.com/SEU_USUARIO/whatsapp-api-railway.git
```

**3.3 Renomeie a branch para main:**
```bash
git branch -M main
```

**3.4 Faça o push inicial:**
```bash
git push -u origin main
```

### 🚀 Passo 4: Deploy no Railway

**4.1 Acesse:** https://railway.app

**4.2 Faça login com GitHub**

**4.3 Clique em "New Project"**

**4.4 Selecione "Deploy from GitHub repo"**

**4.5 Escolha seu repositório:** `whatsapp-api-railway`

**4.6 Railway detectará automaticamente que é Node.js**

### ⚙️ Passo 5: Configurar Variáveis de Ambiente

**5.1 No painel do Railway, clique em "Variables"**

**5.2 Adicione estas variáveis:**

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

### 🎯 Passo 6: Aguardar Deploy

- ⏱️ O deploy leva cerca de 2-3 minutos
- 📊 Acompanhe os logs em tempo real
- ✅ Aguarde até aparecer "Deployed successfully"

### 🔗 Passo 7: Obter URL da API

Após o deploy, você receberá uma URL como:
```
https://whatsapp-api-railway-production.up.railway.app
```

### 🔧 Passo 8: Configurar Laravel

**8.1 Atualize o arquivo `.env` do seu Laravel:**

```env
# Configurações para Railway
WA_SERVER_URL=https://SUA-URL-RAILWAY.up.railway.app
WA_SERVER_HOST=SUA-URL-RAILWAY.up.railway.app
WA_SERVER_PORT=443
WA_SERVER_MAX_RETRIES=5
WA_SERVER_RECONNECT_INTERVAL=5000
```

### 🧪 Passo 9: Testar a API

**9.1 Teste de conectividade:**
```bash
curl https://SUA-URL-RAILWAY.up.railway.app/health
```

**9.2 Teste de QR Code:**
```bash
curl https://SUA-URL-RAILWAY.up.railway.app/test/qr
```

**9.3 Teste no Laravel:**
- Acesse seu painel Laravel
- Vá em "Dispositivos"
- Clique em "Criar Dispositivo"
- Deve aparecer o QR Code

## 🔄 Comandos Úteis para Futuras Atualizações

### Atualizar código no GitHub:
```bash
git add .
git commit -m "📝 Descrição da mudança"
git push origin main
```

### Railway fará deploy automático após cada push! 🚀

## 🆘 Solução de Problemas

### ❌ Erro: "remote origin already exists"
```bash
git remote remove origin
git remote add origin https://github.com/SEU_USUARIO/whatsapp-api-railway.git
```

### ❌ Erro: "failed to push"
```bash
git pull origin main --allow-unrelated-histories
git push origin main
```

### ❌ Deploy falhou no Railway
1. Verifique os logs no painel Railway
2. Confirme que todas as variáveis de ambiente estão configuradas
3. Verifique se o `package.json` está correto

## 🎉 Pronto!

Agora você tem:
- ✅ Código no GitHub
- ✅ API rodando no Railway
- ✅ Laravel conectado
- ✅ Deploy automático configurado

**Qualquer mudança que você fizer e der push, o Railway atualiza automaticamente!** 🚀