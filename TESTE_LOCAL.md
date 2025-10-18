# 🚀 Guia de Teste Local - Sistema WhatsApp

## ✅ Status dos Testes Realizados

### 1. Verificação dos Serviços
- **Laravel**: ✅ Funcionando em http://127.0.0.1:8080
- **Node.js WhatsApp API**: ✅ Funcionando em http://127.0.0.1:8000
- **Banco de dados**: ✅ SQLite configurado e funcionando

### 2. API WhatsApp - Funcionalidades Testadas

#### ✅ Criar Sessão
```bash
# Comando testado:
Invoke-WebRequest -Uri "http://127.0.0.1:8000/sessions/add" -Method POST -ContentType "application/json" -Body '{"id": "minha-sessao", "isLegacy": "false"}'

# Resultado: ✅ Sessão criada com sucesso
# QR Code gerado em formato base64
```

#### ✅ Verificar Status da Sessão
```bash
# Comando testado:
Invoke-WebRequest -Uri "http://127.0.0.1:8000/sessions/status/minha-sessao" -Method GET

# Resultado: ✅ Status retornado (valid_session: false - aguardando autenticação)
```

#### ✅ Status do Servidor
```bash
# Comando testado:
Invoke-WebRequest -Uri "http://127.0.0.1:8000/sessions/server-status" -Method GET

# Resultado: ✅ Servidor respondendo corretamente
```

### 3. Dashboard Laravel
- **Acesso**: ✅ http://127.0.0.1:8080 respondendo
- **Página de instalação**: ✅ Carregando corretamente

### 4. Comunicação Entre Serviços
- **Teste Laravel → API WhatsApp**: ✅ Funcionando
- **Endpoint de teste**: http://127.0.0.1:8080/test/whatsapp-api
- **Resultado**: ✅ Comunicação estabelecida com sucesso

## 🔧 Como Testar Localmente

### Pré-requisitos
- PHP 8.2+ instalado
- Node.js 22+ instalado
- Composer instalado

### Passo 1: Iniciar os Serviços
```bash
# Terminal 1 - Laravel
php artisan serve --host=127.0.0.1 --port=8080

# Terminal 2 - API WhatsApp
npm start
```

### Passo 2: Testar API WhatsApp
```bash
# Criar uma nova sessão
Invoke-WebRequest -Uri "http://127.0.0.1:8000/sessions/add" -Method POST -ContentType "application/json" -Body '{"id": "teste-local", "isLegacy": "false"}'

# Verificar status
Invoke-WebRequest -Uri "http://127.0.0.1:8000/sessions/status/teste-local" -Method GET
```

### Passo 3: Testar Dashboard Laravel
```bash
# Acessar dashboard
Invoke-WebRequest -Uri "http://127.0.0.1:8080" -Method GET

# Testar comunicação entre serviços
Invoke-WebRequest -Uri "http://127.0.0.1:8080/test/whatsapp-api" -Method GET
```

### Passo 4: Conectar WhatsApp (Opcional)
1. Crie uma sessão via API
2. Copie o QR code base64 retornado
3. Decodifique e escaneie com seu WhatsApp
4. Verifique o status da sessão novamente

## 📋 Endpoints Disponíveis

### API WhatsApp (Port 8000)
- `GET /sessions/server-status` - Status do servidor
- `POST /sessions/add` - Criar nova sessão
- `GET /sessions/status/{id}` - Status da sessão
- `DELETE /sessions/delete/{id}` - Deletar sessão
- `GET /chats?id={sessionId}` - Listar chats
- `POST /chats/send?id={sessionId}` - Enviar mensagem

### Laravel (Port 8080)
- `GET /` - Dashboard principal
- `GET /test/whatsapp-api` - Teste de comunicação
- `POST /test/create-session` - Criar sessão via Laravel

## 🐛 Troubleshooting

### Problema: Sessão não encontrada
- **Causa**: Sessão pode ter expirado ou não foi criada corretamente
- **Solução**: Criar nova sessão

### Problema: Erro de conexão entre serviços
- **Causa**: Portas podem estar ocupadas ou serviços não iniciados
- **Solução**: Verificar se ambos os serviços estão rodando

### Problema: QR Code não aparece
- **Causa**: Resposta pode estar sendo truncada
- **Solução**: Verificar logs do servidor Node.js

## 🎯 Próximos Passos

1. **Conectar dispositivo WhatsApp** escaneando QR code
2. **Testar envio de mensagens** via API
3. **Configurar webhooks** para receber mensagens
4. **Preparar para produção** (VPS/Cloud)

## 📝 Logs e Debugging

### Verificar logs Laravel:
```bash
tail -f storage/logs/laravel.log
```

### Verificar logs Node.js:
- Os logs aparecem diretamente no terminal onde `npm start` foi executado

### Verificar sessões criadas:
- Pasta: `sessions/` (criada automaticamente)
- Arquivos de credenciais: `sessions/md_{sessionId}/creds.json`

---

**Status Geral**: ✅ **SISTEMA FUNCIONANDO LOCALMENTE**

Todos os componentes principais estão operacionais e a comunicação entre serviços está estabelecida.