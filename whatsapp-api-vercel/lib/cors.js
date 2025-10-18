/**
 * Configuração de CORS para comunicação entre Vercel e Hostinger
 * Permite requisições do Laravel (Hostinger) para a API WhatsApp (Vercel)
 */

// Lista de origens permitidas
const getAllowedOrigins = () => {
  const origins = [
    process.env.FRONTEND_URL,
    process.env.LARAVEL_URL,
    'http://localhost:8080',
    'http://127.0.0.1:8080',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://startzap.comporhub.com',
    'http://startzap.comporhub.com'
  ].filter(Boolean);

  // Adicionar origens de desenvolvimento se necessário
  if (process.env.NODE_ENV === 'development') {
    origins.push(
      'http://localhost:8000',
      'http://127.0.0.1:8000',
      'http://localhost:5173',
      'http://127.0.0.1:5173'
    );
  }

  return origins;
};

/**
 * Configurar headers CORS na resposta
 */
export function setCorsHeaders(res, origin = null) {
  const allowedOrigins = getAllowedOrigins();
  
  // Verificar se a origem é permitida
  if (origin && allowedOrigins.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
  } else if (!origin || allowedOrigins.length === 0) {
    // Fallback para desenvolvimento ou quando não há origem específica
    res.setHeader('Access-Control-Allow-Origin', '*');
  }

  // Headers permitidos
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
  res.setHeader('Access-Control-Allow-Headers', [
    'Content-Type',
    'Authorization',
    'X-Requested-With',
    'Accept',
    'Origin',
    'X-API-Key',
    'X-Session-ID'
  ].join(', '));

  // Permitir credenciais
  res.setHeader('Access-Control-Allow-Credentials', 'true');

  // Cache do preflight por 24 horas
  res.setHeader('Access-Control-Max-Age', '86400');

  // Headers expostos para o cliente
  res.setHeader('Access-Control-Expose-Headers', [
    'X-Total-Count',
    'X-Page-Count',
    'X-Rate-Limit-Remaining',
    'X-Rate-Limit-Reset'
  ].join(', '));

  // Headers de segurança adicionais
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'DENY');
  res.setHeader('X-XSS-Protection', '1; mode=block');
  
  // CSP básico para APIs
  res.setHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none';");
}

/**
 * Middleware principal de CORS
 */
export function handleCors(req, res, next = null) {
  const origin = req.headers.origin;
  
  // Configurar headers CORS
  setCorsHeaders(res, origin);

  // Responder a requisições OPTIONS (preflight)
  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return true; // Indica que a requisição foi tratada
  }

  // Continuar para o próximo middleware se fornecido
  if (next && typeof next === 'function') {
    next();
  }

  return false; // Indica que a requisição deve continuar
}

/**
 * Middleware de validação de origem
 */
export function validateOrigin(req, res, next = null) {
  const origin = req.headers.origin;
  const allowedOrigins = getAllowedOrigins();
  
  // Em desenvolvimento, permitir qualquer origem
  if (process.env.NODE_ENV === 'development') {
    if (next) next();
    return true;
  }

  // Verificar se a origem é permitida
  if (!origin || !allowedOrigins.includes(origin)) {
    res.status(403).json({
      success: false,
      error: 'Origin not allowed',
      origin: origin || 'unknown',
      allowedOrigins: process.env.NODE_ENV === 'development' ? allowedOrigins : undefined
    });
    return false;
  }

  if (next) next();
  return true;
}

/**
 * Middleware de autenticação por API Key
 */
export function validateApiKey(req, res, next = null) {
  const apiKey = req.headers.authorization || req.headers['x-api-key'];
  const expectedKey = process.env.API_SECRET;

  console.log('API Key validation - Expected:', expectedKey, 'Received:', apiKey);

  // Se não há chave configurada, pular validação
  if (!expectedKey) {
    if (next) next();
    return true;
  }

  // Verificar se a chave foi fornecida
  if (!apiKey) {
    res.status(401).json({
      success: false,
      error: 'API key required',
      message: 'Provide API key in Authorization header or X-API-Key header'
    });
    return false;
  }

  // Verificar se a chave é válida
  if (apiKey !== expectedKey) {
    res.status(401).json({
      success: false,
      error: 'Invalid API key'
    });
    return false;
  }

  if (next) next();
  return true;
}

/**
 * Middleware completo de segurança
 */
export function securityMiddleware(req, res, next = null) {
  // 1. Configurar CORS
  const corsHandled = handleCors(req, res);
  if (corsHandled) return; // OPTIONS request foi tratada

  // 2. Validar origem (apenas em produção)
  if (process.env.NODE_ENV === 'production') {
    const originValid = validateOrigin(req, res);
    if (!originValid) return;
  }

  // 3. Validar API key (se configurada)
  const keyValid = validateApiKey(req, res);
  if (!keyValid) return;

  // 4. Rate limiting básico (em memória)
  const rateLimitPassed = basicRateLimit(req, res);
  if (!rateLimitPassed) return;

  if (next) next();
}

/**
 * Rate limiting básico em memória
 */
const requestCounts = new Map();
const RATE_LIMIT_WINDOW = 60 * 1000; // 1 minuto
const RATE_LIMIT_MAX = 100; // 100 requests por minuto

function basicRateLimit(req, res) {
  const clientId = req.headers['x-forwarded-for'] || req.connection.remoteAddress || 'unknown';
  const now = Date.now();
  
  // Limpar contadores antigos
  for (const [id, data] of requestCounts.entries()) {
    if (now - data.firstRequest > RATE_LIMIT_WINDOW) {
      requestCounts.delete(id);
    }
  }

  // Verificar limite do cliente atual
  const clientData = requestCounts.get(clientId);
  
  if (!clientData) {
    requestCounts.set(clientId, {
      count: 1,
      firstRequest: now
    });
    return true;
  }

  if (now - clientData.firstRequest > RATE_LIMIT_WINDOW) {
    // Reset do contador
    requestCounts.set(clientId, {
      count: 1,
      firstRequest: now
    });
    return true;
  }

  clientData.count++;

  if (clientData.count > RATE_LIMIT_MAX) {
    res.status(429).json({
      success: false,
      error: 'Rate limit exceeded',
      message: `Maximum ${RATE_LIMIT_MAX} requests per minute`,
      retryAfter: Math.ceil((RATE_LIMIT_WINDOW - (now - clientData.firstRequest)) / 1000)
    });
    return false;
  }

  // Adicionar headers de rate limit
  res.setHeader('X-Rate-Limit-Limit', RATE_LIMIT_MAX);
  res.setHeader('X-Rate-Limit-Remaining', RATE_LIMIT_MAX - clientData.count);
  res.setHeader('X-Rate-Limit-Reset', Math.ceil((clientData.firstRequest + RATE_LIMIT_WINDOW) / 1000));

  return true;
}

/**
 * Middleware para logs de requisições
 */
export function requestLogger(req, res, next = null) {
  const start = Date.now();
  const { method, url, headers } = req;
  
  console.log(`[${new Date().toISOString()}] ${method} ${url}`, {
    origin: headers.origin || 'no-origin',
    userAgent: headers['user-agent'] || 'no-user-agent',
    contentType: headers['content-type'] || 'no-content-type'
  });

  // Log da resposta (se possível)
  const originalSend = res.send;
  res.send = function(data) {
    const duration = Date.now() - start;
    console.log(`[${new Date().toISOString()}] ${method} ${url} - ${res.statusCode} (${duration}ms)`);
    originalSend.call(this, data);
  };

  if (next) next();
}

/**
 * Configuração específica para Vercel
 */
export function vercelCorsConfig() {
  return {
    origin: getAllowedOrigins(),
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
    allowedHeaders: [
      'Content-Type',
      'Authorization',
      'X-Requested-With',
      'Accept',
      'Origin',
      'X-API-Key',
      'X-Session-ID'
    ],
    credentials: true,
    maxAge: 86400 // 24 horas
  };
}

/**
 * Utilitário para resposta de erro padronizada
 */
export function sendErrorResponse(res, statusCode, error, details = null) {
  const response = {
    success: false,
    error,
    timestamp: new Date().toISOString()
  };

  if (details && process.env.NODE_ENV === 'development') {
    response.details = details;
  }

  res.status(statusCode).json(response);
}

/**
 * Utilitário para resposta de sucesso padronizada
 */
export function sendSuccessResponse(res, data, statusCode = 200) {
  const response = {
    success: true,
    timestamp: new Date().toISOString(),
    ...data
  };

  res.status(statusCode).json(response);
}

export default {
  handleCors,
  setCorsHeaders,
  validateOrigin,
  validateApiKey,
  securityMiddleware,
  requestLogger,
  vercelCorsConfig,
  sendErrorResponse,
  sendSuccessResponse
};