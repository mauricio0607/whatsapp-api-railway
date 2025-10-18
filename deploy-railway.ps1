# Script de Deploy para Railway (PowerShell)
# Execute este script para preparar e fazer deploy da API WhatsApp no Railway

Write-Host "🚀 Preparando deploy da WhatsApp API para Railway..." -ForegroundColor Green

# Verificar se estamos no diretório correto
if (-not (Test-Path "app.js")) {
    Write-Host "❌ Erro: Execute este script no diretório raiz do projeto" -ForegroundColor Red
    exit 1
}

Write-Host "📋 Verificando arquivos necessários..." -ForegroundColor Yellow

# Verificar arquivos essenciais
$requiredFiles = @("app.js", "package.json", "routes.js", "whatsapp.js")
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "   ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $file não encontrado" -ForegroundColor Red
        exit 1
    }
}

Write-Host "📦 Verificando dependências..." -ForegroundColor Yellow
if (Test-Path "node_modules") {
    Write-Host "   ✅ node_modules encontrado" -ForegroundColor Green
} else {
    Write-Host "   📥 Instalando dependências..." -ForegroundColor Yellow
    npm install
}

Write-Host "🔧 Configurações necessárias no Railway:" -ForegroundColor Cyan
Write-Host "   - NODE_ENV=production" -ForegroundColor Cyan
Write-Host "   - WA_SERVER_HOST=0.0.0.0" -ForegroundColor Cyan
Write-Host "   - WA_SERVER_PORT=8000" -ForegroundColor Cyan
Write-Host "   - SESSION_TIMEOUT=300000" -ForegroundColor Cyan
Write-Host "   - QR_TIMEOUT=60000" -ForegroundColor Cyan
Write-Host "   - MAX_SESSIONS=10" -ForegroundColor Cyan

Write-Host "`n📝 Passos para deploy no Railway:" -ForegroundColor Yellow
Write-Host "1. Acesse https://railway.app" -ForegroundColor White
Write-Host "2. Clique em 'New Project'" -ForegroundColor White
Write-Host "3. Selecione 'Deploy from GitHub repo'" -ForegroundColor White
Write-Host "4. Escolha este repositório" -ForegroundColor White
Write-Host "5. Configure as variáveis de ambiente listadas acima" -ForegroundColor White
Write-Host "6. Aguarde o deploy automático" -ForegroundColor White

Write-Host "`n🔗 Após o deploy:" -ForegroundColor Yellow
Write-Host "1. Copie a URL gerada pelo Railway" -ForegroundColor White
Write-Host "2. Atualize o .env do Laravel:" -ForegroundColor White
Write-Host "   WA_SERVER_URL=https://sua-api.up.railway.app" -ForegroundColor Cyan
Write-Host "   WA_SERVER_HOST=sua-api.up.railway.app" -ForegroundColor Cyan
Write-Host "   WA_SERVER_PORT=443" -ForegroundColor Cyan

Write-Host "`n🧪 Testes após deploy:" -ForegroundColor Yellow
Write-Host "   curl https://sua-api.up.railway.app/health" -ForegroundColor Cyan
Write-Host "   curl https://sua-api.up.railway.app/test/qr" -ForegroundColor Cyan

Write-Host "`n✅ Projeto preparado para deploy no Railway!" -ForegroundColor Green
Write-Host "📖 Consulte README-RAILWAY.md para instruções detalhadas" -ForegroundColor Blue