<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Teste WhatsApp QR Code</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .config-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .qr-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        #qrImage {
            max-width: 300px;
            height: auto;
            border: 3px solid #25D366;
            border-radius: 10px;
        }
        .button {
            background: #25D366;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
            transition: background 0.3s;
        }
        .button:hover {
            background: #128C7E;
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-size: 18px;
        }
        .error {
            background: rgba(231, 76, 60, 0.8);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: none;
        }
        .success {
            background: rgba(39, 174, 96, 0.8);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: none;
        }
        .instructions {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .status {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Teste WhatsApp QR Code</h1>
        
        <div class="config-info">
            <h3>📋 Configuração Atual:</h3>
            <p><strong>URL da API Vercel:</strong> {{ $vercel_url }}</p>
            <p><strong>Status da API Key:</strong> {{ $api_key }}</p>
        </div>
        
        <div style="text-align: center;">
            <button class="button" onclick="gerarQR()">📱 Gerar Novo QR Code</button>
            <button class="button" onclick="testarConexao()">🔍 Testar Conexão</button>
        </div>

        <div id="loading" class="loading" style="display: none;">
            ⏳ Carregando...
        </div>

        <div id="error" class="error"></div>
        <div id="success" class="success"></div>

        <div id="qrContainer" class="qr-container" style="display: none;">
            <h3 style="color: #333; margin-bottom: 20px;">📱 Escaneie este QR Code</h3>
            <img id="qrImage" src="" alt="QR Code WhatsApp">
            <p style="color: #666; margin-top: 15px;">
                <strong>Sessão:</strong> <span id="sessionId"></span>
            </p>
        </div>

        <div class="instructions">
            <h3>📋 Como Conectar:</h3>
            <ol>
                <li>Clique em <strong>"Gerar Novo QR Code"</strong></li>
                <li>Abra o <strong>WhatsApp</strong> no seu celular</li>
                <li>Vá em <strong>Configurações → Aparelhos conectados</strong></li>
                <li>Toque em <strong>"Conectar um aparelho"</strong></li>
                <li>Escaneie o QR code que apareceu acima</li>
                <li>Use <strong>"Testar Conexão"</strong> para verificar o status</li>
            </ol>
        </div>

        <div id="status" class="status">
            <h3>📊 Status da Conexão:</h3>
            <div id="statusContent"></div>
        </div>
    </div>

    <script>
        let currentSessionId = null;

        // Configurar CSRF token para requisições AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function gerarQR() {
            const loading = document.getElementById('loading');
            const qrContainer = document.getElementById('qrContainer');
            const qrImage = document.getElementById('qrImage');
            const sessionIdSpan = document.getElementById('sessionId');
            const error = document.getElementById('error');
            const success = document.getElementById('success');

            // Limpar mensagens anteriores
            error.style.display = 'none';
            success.style.display = 'none';
            qrContainer.style.display = 'none';

            // Mostrar loading
            loading.style.display = 'block';
            loading.textContent = '⏳ Gerando QR Code...';

            try {
                const response = await fetch('/test/vercel/qr-code', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                loading.style.display = 'none';

                if (data.status === 'success' && data.data && data.data.data && data.data.data.qrCode) {
                    // Extrair dados do QR code
                    const qrData = data.data.data;
                    currentSessionId = qrData.sessionId;

                    // Mostrar QR code
                    qrImage.src = qrData.qrCode;
                    sessionIdSpan.textContent = currentSessionId;
                    qrContainer.style.display = 'block';

                    success.textContent = '✅ QR Code gerado com sucesso! Escaneie com seu WhatsApp.';
                    success.style.display = 'block';
                } else {
                    error.textContent = '❌ Erro ao gerar QR Code: ' + (data.message || 'Resposta inválida');
                    error.style.display = 'block';
                }
            } catch (err) {
                loading.style.display = 'none';
                error.textContent = '❌ Erro de conexão: ' + err.message;
                error.style.display = 'block';
            }
        }

        async function testarConexao() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const success = document.getElementById('success');
            const status = document.getElementById('status');
            const statusContent = document.getElementById('statusContent');

            // Limpar mensagens anteriores
            error.style.display = 'none';
            success.style.display = 'none';
            status.style.display = 'none';

            // Mostrar loading
            loading.style.display = 'block';
            loading.textContent = '⏳ Testando conexão...';

            try {
                const response = await fetch('/test/vercel/sessions', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                loading.style.display = 'none';

                if (data.status === 'success') {
                    success.textContent = '✅ Conexão com a API funcionando perfeitamente!';
                    success.style.display = 'block';

                    // Mostrar informações das sessões
                    statusContent.innerHTML = `
                        <p><strong>Status:</strong> ${data.message}</p>
                        <p><strong>Sessões encontradas:</strong> ${data.data.data ? data.data.data.length : 0}</p>
                        <p><strong>Timestamp:</strong> ${data.timestamp}</p>
                    `;
                    status.style.display = 'block';
                } else {
                    error.textContent = '❌ Erro na conexão: ' + data.message;
                    error.style.display = 'block';
                }
            } catch (err) {
                loading.style.display = 'none';
                error.textContent = '❌ Erro de conexão: ' + err.message;
                error.style.display = 'block';
            }
        }

        // Auto-gerar QR code ao carregar a página
        window.addEventListener('load', function() {
            setTimeout(gerarQR, 1000);
        });
    </script>
</body>
</html>