# 🚀 Guia Passo a Passo: Deploy Híbrido

## 📋 Resumo da Estratégia

* **Node.js WhatsApp API** → Vercel (Gratuito, Serverless)

* **Laravel Dashboard** → Hostinger (Seu host atual)

* **Comunicação** → HTTPS entre os serviços

## 🎯 Parte 1: Preparar API para Vercel

### Passo 1: Criar Pasta da API

```bash
# No seu computador, criar nova pasta
mkdir whatsapp-api-vercel
cd whatsapp-api-vercel
```

### Passo 2: Copiar Arquivos Base

Copie estes arquivos do seu projeto atual:

* `whatsapp.js` → `lib/whatsapp.js`

* `routes.js` → Dividir em arquivos da pasta `api/`

* `package.json` → Atualizar para Vercel

### Passo 3: Estrutura Final

```
whatsapp-api-vercel/
├── api/
│   ├── sessions/
│   │   ├── index.js          # Criar/Listar sessões
│   │   └── [sessionId].js    # Status/Deletar sessão
│   ├── chats/
│   │   └── send.js           # Enviar mensagens
│   └── test/
│       └── qr.js             # Teste de QR code
├── lib/
│   ├── whatsapp.js           # Lógica do WhatsApp
│   └── cors.js               # Configuração CORS
├── package.json              # Dependências
├── vercel.json               # Configuração Vercel
└── .env.example              # Exemplo de variáveis
```

### Passo 4: Instalar Vercel CLI

```bash
# Instalar globalmente
npm install -g vercel

# Fazer login
vercel login
```

### Passo 5: Deploy no Vercel

```bash
# Na pasta whatsapp-api-vercel
npm install

# Testar localmente
vercel dev

# Deploy para produção
vercel --prod
```

### Passo 6: Configurar Variáveis no Vercel

```bash
# Configurar variáveis de ambiente
vercel env add FRONTEND_URL
# Digite: https://seudominio.com

vercel env add API_SECRET
# Digite: uma_chave_secreta_forte_123

vercel env add NODE_ENV
# Digite: production
```

## 🏠 Parte 2: Configurar Laravel no Hostinger

### Passo 1: Backup do Projeto Atual

```bash
# Fazer backup completo
zip -r whatsapp-backup-$(date +%Y%m%d).zip .
```

### Passo 2: Atualizar .env do Laravel

```env
# Adicionar/Atualizar estas linhas no .env
WA_SERVER_URL=https://sua-api.vercel.app
WA_SERVER_HOST=sua-api.vercel.app
WA_SERVER_PORT=443
WA_API_SECRET=uma_chave_secreta_forte_123

# Manter configurações existentes do banco
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=seu_database_atual
DB_USERNAME=seu_usuario_atual
DB_PASSWORD=sua_senha_atual
```

### Passo 3: Atualizar Service do WhatsApp

Substituir `app/Services/WhatsAppService.php` com a nova versão que faz requisições HTTP para o Vercel.

### Passo 4: Testar Conectividade

```php
// Criar rota de teste
Route::get('/test-api', function() {
    $service = new \App\Services\WhatsAppService();
    return $service->testConnection();
});
```

## 🔗 Parte 3: Conectar os Serviços

### Passo 1: URLs Finais

Após o deploy, você terá:

* **Laravel**: `https://seudominio.com`

* **API Vercel**: `https://sua-api-whatsapp.vercel.app`

### Passo 2: Configurar CORS

No Vercel, a API já estará configurada para aceitar requisições do seu domínio Laravel.

### Passo 3: Testar Integração

1. **Acessar Laravel**: `https://seudominio.com`
2. **Criar sessão WhatsApp** via dashboard
3. **Verificar se QR code aparece**
4. **Escanear com WhatsApp**
5. **Testar envio de mensagem**

## 🛠️ Comandos Rápidos

### Deploy da API (Vercel)

```bash
# Pasta da API
cd whatsapp-api-vercel

# Deploy
vercel --prod

# Ver logs
vercel logs
```

### Atualizar Laravel (Hostinger)

```bash
# Via SSH ou cPanel Terminal
php artisan config:cache
php artisan route:cache
php artisan cache:clear
```

## 🔍 Troubleshooting

### Problema: API não responde

```bash
# Verificar logs do Vercel
vercel logs https://sua-api.vercel.app

# Testar endpoint diretamente
curl https://sua-api.vercel.app/api/test/qr
```

### Problema: CORS Error

Verificar se `FRONTEND_URL` no Vercel está correto:

```bash
vercel env ls
```

### Problema: Timeout

APIs serverless têm limite de 10s (Hobby) ou 60s (Pro). Otimizar código para ser mais rápido.

## 📊 Monitoramento

### Logs do Vercel

```bash
# Ver logs em tempo real
vercel logs --follow

# Logs de função específica
vercel logs https://sua-api.vercel.app/api/sessions
```

### Logs do Laravel

```bash
# Via SSH
tail -f storage/logs/laravel.log

# Via cPanel: File Manager → storage/logs/
```

## 💰 Custos

### Vercel (Gratuito)

* ✅ 100GB bandwidth/mês

* ✅ Domínio .vercel.app

* ✅ SSL automático

* ⚠️ Timeout: 10s por função

### Hostinger (Atual)

* ✅ Mantém plano atual

* ✅ Database MySQL

* ✅ SSL existente

* ✅ Sem custos adicionais

## 🎯 Vantagens da Estratégia

1. **Custo Zero**: Vercel gratuito + Hostinger atual
2. **Escalabilidade**: API serverless escala automaticamente
3. **Confiabilidade**: Vercel tem 99.9% uptime
4. **Simplicidade**: Não precisa configurar servidor
5. **SSL Automático**: HTTPS em ambos os serviços

## 📋 Checklist Final

### Preparação

* [ ] Backup do projeto atual

* [ ] Criar pasta `whatsapp-api-vercel`

* [ ] Copiar e adaptar arquivos

* [ ] Instalar Vercel CLI

### Deploy API

* [ ] `vercel login`

* [ ] `vercel dev` (testar local)

* [ ] `vercel --prod` (deploy)

* [ ] Configurar variáveis de ambiente

* [ ] Testar endpoints

### Configurar Laravel

* [ ] Atualizar `.env`

* [ ] Atualizar `WhatsAppService.php`

* [ ] Testar conectividade

* [ ] Limpar caches

### Teste Final

* [ ] Acessar dashboard Laravel

* [ ] Criar sessão WhatsApp

* [ ] Escanear QR code

* [ ] Enviar mensagem de teste

* [ ] Verificar logs

## 🚀 Próximos Passos

1. **Seguir este guia passo a passo**
2. **Testar cada etapa**
3. **Documentar URLs finais**
4. **Configurar monitoramento**
5. **Fazer backup regular**

**Pronto! Com este guia você terá um sistema híbrido funcionando perfeitamente! 🎉**

***

## 📞 Suporte

Se encontrar problemas:

1. Verificar logs do Vercel
2. Verificar logs do Laravel
3. Testar conectividade entre serviços
4. Verificar configurações de CORS
5. Validar variáveis de ambiente

