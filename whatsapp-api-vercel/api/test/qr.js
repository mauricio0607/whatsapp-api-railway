/**
 * API Endpoint: /api/test/qr
 * Endpoint de teste para geração de QR code WhatsApp
 * 
 * GET  /api/test/qr           - Gerar QR code de teste
 * POST /api/test/qr           - Criar sessão de teste e gerar QR
 */

import { whatsappManager } from '../../lib/whatsapp.js';
import { securityMiddleware, sendErrorResponse, sendSuccessResponse } from '../../lib/cors.js';

export default async function handler(req, res) {
  try {
    // Aplicar middleware de segurança
    securityMiddleware(req, res, () => {});

    // Se foi tratado pelo middleware (OPTIONS), retornar
    if (res.headersSent) {
      return;
    }

    const { method, query, body } = req;

    switch (method) {
      case 'GET':
        return await handleGetTestQR(req, res, query);
      
      case 'POST':
        return await handleCreateTestSession(req, res, body);
      
      default:
        return sendErrorResponse(res, 405, 'Method not allowed', {
          allowedMethods: ['GET', 'POST'],
          receivedMethod: method
        });
    }

  } catch (error) {
    console.error('Test QR API Error:', error);
    return sendErrorResponse(res, 500, 'Internal server error', {
      message: error.message,
      stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
}

/**
 * GET /api/test/qr
 * Gerar QR code de teste rápido
 */
async function handleGetTestQR(req, res, query) {
  try {
    const { 
      sessionId,        // ID da sessão (opcional, usa 'test-session' por padrão)
      format,           // Formato: json, html, svg, png
      size,             // Tamanho do QR: small, medium, large
      refresh           // Forçar refresh: true/false
    } = query;

    const testSessionId = sessionId || 'test-session';

    console.log(`Generating test QR code:`, { 
      sessionId: testSessionId,
      format: format || 'json',
      size: size || 'medium',
      refresh: refresh === 'true'
    });

    // Verificar se sessão de teste existe
    let session = whatsappManager.getSession(testSessionId);
    
    // Se não existe ou refresh foi solicitado, criar nova
    if (!session || refresh === 'true') {
      console.log(`Creating new test session: ${testSessionId}`);
      
      // Deletar sessão existente se refresh
      if (session && refresh === 'true') {
        await whatsappManager.deleteSession(testSessionId, { force: true });
      }

      // Criar nova sessão
      const createResult = await whatsappManager.createSession(testSessionId);
      
      if (!createResult.success) {
        return sendErrorResponse(res, 500, 'Failed to create test session', {
          error: createResult.error,
          sessionId: testSessionId,
          details: createResult.details
        });
      }

      session = whatsappManager.getSession(testSessionId);
    }

    // Obter status da sessão
    const status = whatsappManager.getSessionStatus(testSessionId);

    // Se não tem QR code, tentar gerar
    if (!status.qrCode && !status.isConnected) {
      console.log(`No QR code available, attempting to generate for session: ${testSessionId}`);
      
      const refreshResult = await whatsappManager.refreshQR(testSessionId);
      if (!refreshResult.success) {
        return sendErrorResponse(res, 400, 'Failed to generate QR code', {
          error: refreshResult.error,
          sessionId: testSessionId,
          currentStatus: status.status
        });
      }
    }

    // Obter status atualizado
    const updatedStatus = whatsappManager.getSessionStatus(testSessionId);

    // Preparar resposta baseada no formato
    const responseFormat = format || 'json';

    switch (responseFormat) {
      case 'html':
        return handleHTMLResponse(res, updatedStatus, testSessionId, size);
      
      case 'svg':
        return handleSVGResponse(res, updatedStatus, testSessionId, size);
      
      case 'png':
        return handlePNGResponse(res, updatedStatus, testSessionId, size);
      
      case 'json':
      default:
        return handleJSONResponse(res, updatedStatus, testSessionId, query);
    }

  } catch (error) {
    console.error('Error generating test QR:', error);
    return sendErrorResponse(res, 500, 'Failed to generate test QR', error.message);
  }
}

/**
 * POST /api/test/qr
 * Criar sessão de teste personalizada
 */
async function handleCreateTestSession(req, res, body) {
  try {
    const { 
      sessionId,        // ID personalizado da sessão
      options,          // Opções da sessão
      autoConnect       // Conectar automaticamente
    } = body || {};

    // Gerar ID único se não fornecido
    const testSessionId = sessionId || `test-${Date.now()}`;

    // Validar sessionId
    if (sessionId && typeof sessionId !== 'string') {
      return sendErrorResponse(res, 400, 'Invalid sessionId', {
        message: 'sessionId must be a string',
        received: typeof sessionId
      });
    }

    // Verificar se sessionId já existe
    const existingSession = whatsappManager.getSession(testSessionId);
    if (existingSession) {
      const status = whatsappManager.getSessionStatus(testSessionId);
      return sendErrorResponse(res, 409, 'Test session already exists', {
        sessionId: testSessionId,
        currentStatus: status.status,
        isConnected: status.isConnected,
        message: 'Use a different sessionId or delete the existing session first'
      });
    }

    console.log(`Creating custom test session:`, { 
      sessionId: testSessionId,
      options: options || {},
      autoConnect: autoConnect !== false
    });

    // Criar sessão de teste
    const result = await whatsappManager.createSession(testSessionId, options);

    if (!result.success) {
      return sendErrorResponse(res, 400, 'Failed to create test session', {
        error: result.error,
        sessionId: testSessionId,
        details: result.details
      });
    }

    // Preparar resposta
    const response = {
      sessionId: testSessionId,
      status: result.status,
      qrCode: result.qrCode,
      message: 'Test session created successfully',
      isTest: true,
      expiresIn: result.expiresIn,
      instructions: {
        scan: 'Scan the QR code with WhatsApp to connect',
        checkStatus: `/api/sessions/${testSessionId}`,
        deleteSession: `/api/sessions/${testSessionId}`,
        sendTestMessage: '/api/chats/send',
        refreshQR: `/api/test/qr?sessionId=${testSessionId}&refresh=true`
      },
      testEndpoints: {
        htmlQR: `/api/test/qr?sessionId=${testSessionId}&format=html`,
        svgQR: `/api/test/qr?sessionId=${testSessionId}&format=svg`,
        pngQR: `/api/test/qr?sessionId=${testSessionId}&format=png`
      },
      createdAt: new Date().toISOString()
    };

    console.log(`Test session created successfully:`, {
      sessionId: testSessionId,
      status: result.status,
      hasQR: !!result.qrCode
    });

    return sendSuccessResponse(res, response, 201);

  } catch (error) {
    console.error('Error creating test session:', error);
    return sendErrorResponse(res, 500, 'Failed to create test session', error.message);
  }
}

/**
 * Resposta em formato JSON
 */
function handleJSONResponse(res, status, sessionId, query) {
  const response = {
    sessionId,
    status: status.status,
    isConnected: status.isConnected,
    qrCode: status.qrCode,
    hasQR: !!status.qrCode,
    expiresAt: status.qrExpiresAt,
    message: status.isConnected 
      ? 'Session is already connected' 
      : status.qrCode 
        ? 'QR code ready for scanning'
        : 'QR code not available',
    instructions: status.qrCode ? {
      scan: 'Scan the QR code with WhatsApp mobile app',
      timeout: 'QR code expires in 60 seconds',
      refresh: `/api/test/qr?sessionId=${sessionId}&refresh=true`
    } : {
      status: 'Check session status',
      refresh: 'Try refreshing to generate new QR code'
    },
    formats: {
      html: `/api/test/qr?sessionId=${sessionId}&format=html`,
      svg: `/api/test/qr?sessionId=${sessionId}&format=svg`,
      png: `/api/test/qr?sessionId=${sessionId}&format=png`
    },
    generatedAt: new Date().toISOString()
  };

  return sendSuccessResponse(res, response);
}

/**
 * Resposta em formato HTML
 */
function handleHTMLResponse(res, status, sessionId, size) {
  const qrSize = getQRSize(size);
  
  if (!status.qrCode) {
    const html = `
<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp QR Code - Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { color: #e74c3c; }
        .button { background: #25d366; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; }
        .button:hover { background: #128c7e; }
    </style>
</head>
<body>
    <div class="container">
        <h2>WhatsApp QR Code Test</h2>
        <div class="error">
            <p>❌ QR Code não disponível</p>
            <p>Status: ${status.status}</p>
            <p>Conectado: ${status.isConnected ? 'Sim' : 'Não'}</p>
        </div>
        <a href="/api/test/qr?sessionId=${sessionId}&format=html&refresh=true" class="button">🔄 Gerar Novo QR</a>
        <a href="/api/sessions/${sessionId}" class="button">📊 Ver Status</a>
    </div>
</body>
</html>`;
    
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    return res.status(200).send(html);
  }

  const html = `
<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp QR Code - Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .qr-container { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .qr-code { max-width: 100%; height: auto; border: 2px solid #25d366; border-radius: 8px; }
        .status { color: #27ae60; font-weight: bold; }
        .instructions { text-align: left; margin: 20px 0; padding: 15px; background: #e8f5e8; border-radius: 5px; }
        .button { background: #25d366; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #128c7e; }
        .button.secondary { background: #3498db; }
        .button.secondary:hover { background: #2980b9; }
        .info { font-size: 12px; color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📱 WhatsApp QR Code Test</h2>
        <div class="status">✅ QR Code Pronto para Escaneamento</div>
        
        <div class="qr-container">
            <img src="data:image/png;base64,${status.qrCode}" alt="WhatsApp QR Code" class="qr-code" width="${qrSize}" height="${qrSize}">
        </div>
        
        <div class="instructions">
            <h4>📋 Instruções:</h4>
            <ol>
                <li>Abra o WhatsApp no seu celular</li>
                <li>Toque em "Mais opções" (⋮) e depois em "Aparelhos conectados"</li>
                <li>Toque em "Conectar um aparelho"</li>
                <li>Escaneie este QR code</li>
            </ol>
        </div>
        
        <div>
            <a href="/api/test/qr?sessionId=${sessionId}&format=html&refresh=true" class="button">🔄 Gerar Novo QR</a>
            <a href="/api/sessions/${sessionId}" class="button secondary">📊 Ver Status</a>
            <a href="/api/test/qr?sessionId=${sessionId}&format=json" class="button secondary">📄 JSON</a>
        </div>
        
        <div class="info">
            <p>Sessão: ${sessionId}</p>
            <p>Gerado em: ${new Date().toLocaleString('pt-BR')}</p>
            <p>⏰ QR Code expira em 60 segundos</p>
        </div>
    </div>
    
    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>`;

  res.setHeader('Content-Type', 'text/html; charset=utf-8');
  return res.status(200).send(html);
}

/**
 * Resposta em formato SVG
 */
function handleSVGResponse(res, status, sessionId, size) {
  if (!status.qrCode) {
    return sendErrorResponse(res, 404, 'QR code not available', {
      sessionId,
      status: status.status,
      message: 'No QR code available for this session'
    });
  }

  // Converter base64 para SVG (simplificado)
  const qrSize = getQRSize(size);
  
  const svg = `
<svg width="${qrSize}" height="${qrSize}" xmlns="http://www.w3.org/2000/svg">
  <rect width="100%" height="100%" fill="white"/>
  <image href="data:image/png;base64,${status.qrCode}" width="${qrSize}" height="${qrSize}"/>
  <text x="50%" y="95%" text-anchor="middle" font-family="Arial" font-size="12" fill="#666">
    Session: ${sessionId}
  </text>
</svg>`;

  res.setHeader('Content-Type', 'image/svg+xml');
  res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  return res.status(200).send(svg);
}

/**
 * Resposta em formato PNG
 */
function handlePNGResponse(res, status, sessionId, size) {
  if (!status.qrCode) {
    return sendErrorResponse(res, 404, 'QR code not available', {
      sessionId,
      status: status.status,
      message: 'No QR code available for this session'
    });
  }

  // Retornar a imagem base64 como PNG
  const imageBuffer = Buffer.from(status.qrCode, 'base64');
  
  res.setHeader('Content-Type', 'image/png');
  res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  res.setHeader('Content-Disposition', `inline; filename="whatsapp-qr-${sessionId}.png"`);
  
  return res.status(200).send(imageBuffer);
}

/**
 * Obter tamanho do QR code baseado no parâmetro
 */
function getQRSize(size) {
  switch (size) {
    case 'small':
      return 200;
    case 'large':
      return 400;
    case 'medium':
    default:
      return 300;
  }
}

/**
 * Validação de entrada para criação de sessão de teste
 */
function validateTestSessionInput(body) {
  const errors = [];

  if (body.sessionId !== undefined) {
    if (typeof body.sessionId !== 'string') {
      errors.push('sessionId must be a string');
    } else if (body.sessionId.length === 0) {
      errors.push('sessionId cannot be empty');
    } else if (body.sessionId.length > 50) {
      errors.push('sessionId must be 50 characters or less');
    } else if (!/^[a-zA-Z0-9_-]+$/.test(body.sessionId)) {
      errors.push('sessionId can only contain letters, numbers, hyphens and underscores');
    }
  }

  if (body.autoConnect !== undefined && typeof body.autoConnect !== 'boolean') {
    errors.push('autoConnect must be a boolean');
  }

  if (body.options !== undefined && typeof body.options !== 'object') {
    errors.push('options must be an object');
  }

  return errors;
}