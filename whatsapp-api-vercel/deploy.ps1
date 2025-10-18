# Script de Deploy para WhatsApp API no Vercel (PowerShell)
# Autor: Sistema de Deploy Híbrido

Write-Host "🚀 Iniciando deploy da WhatsApp API para Vercel..." -ForegroundColor Green

# Verificar se está no diretório correto
if (-not (Test-Path "package.json")) {
    Write-Host "❌ Erro: Execute este script no diretório whatsapp-api-vercel" -ForegroundColor Red
    exit 1
}

# Verificar se o Node.js está instalado
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js encontrado: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js não encontrado. Instale o Node.js primeiro." -ForegroundColor Red
    exit 1
}

# Verificar se o Vercel CLI está instalado
try {
    $vercelVersion = vercel --version
    Write-Host "✅ Vercel CLI encontrado: $vercelVersion" -ForegroundColor Green
} catch {
    Write-Host "📦 Instalando Vercel CLI..." -ForegroundColor Yellow
    npm install -g vercel
}

# Verificar se existe arquivo .env
if (-not (Test-Path ".env")) {
    Write-Host "⚠️  Arquivo .env não encontrado. Copiando .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    Write-Host "📝 Configure as variáveis de ambiente no arquivo .env antes de continuar" -ForegroundColor Cyan
    Write-Host "   Principais variáveis:" -ForegroundColor Cyan
    Write-Host "   - WHATSAPP_API_KEY" -ForegroundColor Cyan
    Write-Host "   - CORS_ORIGIN" -ForegroundColor Cyan
    Write-Host "   - NODE_ENV=production" -ForegroundColor Cyan
    Read-Host "Pressione Enter após configurar o .env"
}

# Instalar dependências
Write-Host "📦 Instalando dependências..." -ForegroundColor Yellow
npm install

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erro ao instalar dependências" -ForegroundColor Red
    exit 1
}

# Executar testes (se existirem)
$packageJson = Get-Content "package.json" | ConvertFrom-Json
if ($packageJson.scripts.test) {
    Write-Host "🧪 Executando testes..." -ForegroundColor Yellow
    npm test
}

# Fazer login no Vercel (se necessário)
Write-Host "🔐 Verificando autenticação Vercel..." -ForegroundColor Yellow
try {
    vercel whoami
    Write-Host "✅ Usuário autenticado no Vercel" -ForegroundColor Green
} catch {
    Write-Host "🔑 Fazendo login no Vercel..." -ForegroundColor Yellow
    vercel login
}

# Deploy para Vercel
Write-Host "🚀 Fazendo deploy para Vercel..." -ForegroundColor Green
vercel --prod

# Verificar se o deploy foi bem-sucedido
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Deploy realizado com sucesso!" -ForegroundColor Green
    
    # Obter URL do projeto
    $projectInfo = vercel ls --json | ConvertFrom-Json
    $projectUrl = $projectInfo | Where-Object { $_.name -like "*whatsapp*" } | Select-Object -First 1 -ExpandProperty url
    
    if ($projectUrl) {
        Write-Host "🌐 Sua API está disponível em: https://$projectUrl" -ForegroundColor Cyan
    }
    
    Write-Host ""
    Write-Host "📋 Próximos passos:" -ForegroundColor Cyan
    Write-Host "1. Atualize a variável WHATSAPP_VERCEL_API_URL no Laravel" -ForegroundColor White
    Write-Host "2. Configure as variáveis de ambiente no painel Vercel" -ForegroundColor White
    Write-Host "3. Teste a conexão usando o endpoint /api/test/qr" -ForegroundColor White
} else {
    Write-Host "❌ Erro durante o deploy. Verifique os logs acima." -ForegroundColor Red
    exit 1
}

Write-Host "🎉 Deploy concluído!" -ForegroundColor Green