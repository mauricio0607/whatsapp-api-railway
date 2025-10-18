#!/bin/bash

# Script de Deploy para WhatsApp API no Vercel
# Autor: Sistema de Deploy Híbrido
# Data: $(date)

echo "🚀 Iniciando deploy da WhatsApp API para Vercel..."

# Verificar se está no diretório correto
if [ ! -f "package.json" ]; then
    echo "❌ Erro: Execute este script no diretório whatsapp-api-vercel"
    exit 1
fi

# Verificar se o Vercel CLI está instalado
if ! command -v vercel &> /dev/null; then
    echo "📦 Instalando Vercel CLI..."
    npm install -g vercel
fi

# Verificar se existe arquivo .env
if [ ! -f ".env" ]; then
    echo "⚠️  Arquivo .env não encontrado. Copiando .env.example..."
    cp .env.example .env
    echo "📝 Configure as variáveis de ambiente no arquivo .env antes de continuar"
    echo "   Principais variáveis:"
    echo "   - WHATSAPP_API_KEY"
    echo "   - CORS_ORIGIN"
    echo "   - NODE_ENV=production"
    read -p "Pressione Enter após configurar o .env..."
fi

# Instalar dependências
echo "📦 Instalando dependências..."
npm install

# Executar testes (se existirem)
if [ -f "package.json" ] && grep -q "test" package.json; then
    echo "🧪 Executando testes..."
    npm test
fi

# Fazer login no Vercel (se necessário)
echo "🔐 Verificando autenticação Vercel..."
vercel whoami || vercel login

# Deploy para Vercel
echo "🚀 Fazendo deploy para Vercel..."
vercel --prod

# Verificar se o deploy foi bem-sucedido
if [ $? -eq 0 ]; then
    echo "✅ Deploy realizado com sucesso!"
    echo "🌐 Sua API está disponível em: $(vercel ls | grep whatsapp-api | awk '{print $2}' | head -1)"
    echo ""
    echo "📋 Próximos passos:"
    echo "1. Atualize a variável WHATSAPP_VERCEL_API_URL no Laravel"
    echo "2. Configure as variáveis de ambiente no painel Vercel"
    echo "3. Teste a conexão usando o endpoint /api/test/qr"
else
    echo "❌ Erro durante o deploy. Verifique os logs acima."
    exit 1
fi

echo "🎉 Deploy concluído!"