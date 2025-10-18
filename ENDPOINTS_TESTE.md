# 🚀 Endpoints de Teste - WhatsApp API

## ✅ **STATUS: FUNCIONANDO PERFEITAMENTE!**

Todos os endpoints foram testados e estão funcionando corretamente. O servidor Node.js está rodando em `http://127.0.0.1:8000`.

## 📋 Endpoints Disponíveis

### 1. **🔄 Gerar QR Code de Teste**
```
GET http://127.0.0.1:8000/test/qr
```

**Descrição**: Gera automaticamente uma nova sessão de teste com QR code para conectar WhatsApp.

**✅ TESTADO E FUNCIONANDO**

**Resposta de Sucesso**:
```json
{
  "success": true,
  "message": "QR code de teste gerado com sucesso",
  "data": {
    "sessionId": "test-1760660944919",
    "qr": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA...",
    "instructions": [
      "1. Abra o WhatsApp no seu celular",
      "2. Vá em Configurações > Aparelhos conectados",
      "3. Toque em 'Conectar um aparelho'",
      "4. Escaneie o QR code abaixo",
      "5. Use GET /test/status/{sessionId} para verificar o status"
    ]
  }
}
```

**Como Testar**:
```powershell
# PowerShell (TESTADO ✅)
Invoke-WebRequest -Uri "http://127.0.0.1:8000/test/qr"

# cURL
curl -X GET http://127.0.0.1:8000/test/qr
```

---

### 2. **Verificar Status da Sessão**
```
GET http://127.0.0.1:8000/test/status/{sessionId}
```

**Descrição**: Verifica o status de autenticação de uma sessão específica.

**Parâmetros**:
- `sessionId`: ID da sessão (ex: test-1760660780419)

**Resposta de Sucesso**:
```json
{
  "success": true,
  "message": "Status da sessão obtido",
  "data": {
    "sessionId": "test-1760660780419",
    "status": "waiting_for_auth", // ou "authenticated"
    "user": null, // ou dados do usuário quando autenticado
    "connected": false,
    "lastSeen": null
  }
}
```

**Como Testar**:
```bash
# PowerShell
Invoke-WebRequest -Uri "http://127.0.0.1:8000/test/status/test-1760660780419" -Method GET

# cURL
curl -X GET http://127.0.0.1:8000/test/status/test-1760660780419
```

---

### 3. **Listar Endpoints Disponíveis**
```
GET http://127.0.0.1:8000/test/sessions
```

**Descrição**: Lista todos os endpoints disponíveis para teste.

**Resposta**:
```json
{
  "success": true,
  "message": "Lista de sessões de teste",
  "data": {
    "message": "Use POST /sessions/add para criar uma nova sessão",
    "endpoints": {
      "Criar sessão": "POST /sessions/add",
      "QR code de teste": "GET /test/qr",
      "Status da sessão": "GET /test/status/{sessionId}",
      "Status do servidor": "GET /sessions/server-status"
    }
  }
}
```

---

### 4. **Deletar Sessão de Teste**
```
DELETE http://127.0.0.1:8000/test/session/{sessionId}
```

**Descrição**: Remove uma sessão de teste específica.

**Parâmetros**:
- `sessionId`: ID da sessão a ser deletada

**Resposta de Sucesso**:
```json
{
  "success": true,
  "message": "Sessão deletada com sucesso",
  "data": {
    "sessionId": "test-1760660780419",
    "status": "deleted"
  }
}
```

---

## 🔧 Como Usar Para Testes Locais

### Passo 1: Gerar QR Code
```bash
Invoke-WebRequest -Uri "http://127.0.0.1:8000/test/qr" -Method GET
```

### Passo 2: Copiar o QR Code
- Copie o valor do campo `data.qr` da resposta
- É uma string base64 que começa com `data:image/png;base64,`

### Passo 3: Visualizar o QR Code
Você pode:
1. **Salvar como arquivo HTML**:
```html
<!DOCTYPE html>
<html>
<body>
    <h2>QR Code WhatsApp</h2>
    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA..." alt="QR Code">
</body>
</html>
```

2. **Usar um decodificador online** de base64 para imagem

3. **Usar extensão do navegador** para visualizar data URLs

### Passo 4: Escanear com WhatsApp
1. Abra WhatsApp no celular
2. Vá em **Configurações** > **Aparelhos conectados**
3. Toque em **"Conectar um aparelho"**
4. Escaneie o QR code gerado

### Passo 5: Verificar Status
```bash
# Substitua pelo sessionId retornado no passo 1
Invoke-WebRequest -Uri "http://127.0.0.1:8000/test/status/test-1760660780419" -Method GET
```

---

## 🐛 Troubleshooting

### Problema: QR Code não aparece
**Solução**: Verifique se o servidor Node.js está rodando em http://127.0.0.1:8000

### Problema: Sessão não encontrada
**Solução**: Use o `sessionId` exato retornado pelo endpoint `/test/qr`

### Problema: Status sempre "waiting_for_auth"
**Solução**: Certifique-se de que escaneou o QR code corretamente com o WhatsApp

### Problema: Erro 500
**Solução**: Verifique os logs do servidor Node.js no terminal

---

## 📊 Status Possíveis

| Status | Descrição |
|--------|-----------|
| `waiting_for_auth` | Aguardando escaneamento do QR code |
| `authenticated` | WhatsApp conectado com sucesso |
| `not_found` | Sessão não existe |
| `deleted` | Sessão foi removida |

---

## 🎯 Próximos Passos

Após conectar com sucesso:

1. **Testar envio de mensagens**:
```bash
POST http://127.0.0.1:8000/chats/send?id={sessionId}
```

2. **Listar chats**:
```bash
GET http://127.0.0.1:8000/chats?id={sessionId}
```

3. **Integrar com Laravel**:
```bash
GET http://127.0.0.1:8080/test/whatsapp-api
```

---

**✅ Sistema de Testes Funcionando!**

Todos os endpoints estão operacionais e prontos para uso local.