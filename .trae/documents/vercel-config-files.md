# 📁 Arquivos de Configuração para Vercel

## 🚀 Estrutura de Arquivos

Crie uma nova pasta `whatsapp-api-vercel/` com esta estrutura:

```
whatsapp-api-vercel/
├── api/
│   ├── sessions/
│   │   ├── index.js          # GET/POST /api/sessions
│   │   └── [sessionId].js    # GET/DELETE /api/sessions/:id
│   ├── chats/
│   │   ├── send.js           # POST /api/chats/send
│   │   └── [sessionId].js    # GET /api/chats/:sessionId
│   ├── groups/
│   │   ├── create.js         # POST /api/groups/create
│   │   └── [sessionId].js    # GET /api/groups/:sessionId
│   └── test/
│       ├── qr.js             # GET /api/test/qr
│       └── status.js         # GET /api/test/status
├── lib/
│   ├── whatsapp.js
│   ├── utils.js
│   └── cors.js
├── package.json
├── vercel.json
├── .env.example
└── README.md
```

## 📄 Arquivo: `vercel.json`

```json
{
  "version": 2,
  "name": "whatsapp-api",
  "builds": [
    {
      "src": "api/**/*.js",
      "use": "@vercel/node"
    }
  ],
  "routes": [
    {
      "src": "/api/(.*)",
      "dest": "/api/$1"
    },
    {
      "src": "/(.*)",
      "dest": "/api/$1"
    }
  ],
  "env": {
    "NODE_ENV": "production"
  },
  "functions": {
    "api/**/*.js": {
      "maxDuration": 25
    }
  },
  "regions": ["gru1"]
}
```

## 📄 Arquivo: `package.json`

```json
{
  "name": "whatsapp-api-vercel",
  "version": "1.0.0",
  "description": "WhatsApp API Serverless for Vercel",
  "main": "api/index.js",
  "scripts": {
    "dev": "vercel dev",
    "build": "echo 'No build step required'",
    "start": "node api/index.js",
    "deploy": "vercel --prod"
  },
  "dependencies": {
    "@adiwajshing/baileys": "^6.6.0",
    "qrcode": "^1.5.3",
    "axios": "^1.6.0",
    "cors": "^2.8.5",
    "pino": "^8.16.0",
    "node-cache": "^5.1.2",
    "uuid": "^9.0.1"
  },
  "engines": {
    "node": "18.x"
  },
  "keywords": [
    "whatsapp",
    "api",
    "vercel",
    "serverless",
    "baileys"
  ],
  "author": "Seu Nome",
  "license": "MIT"
}
```

## 📄 Arquivo: `lib/cors.js`

```javascript
export function setCorsHeaders(res, origin = '*') {
  const allowedOrigins = [
    process.env.FRONTEND_URL,
    'https://seudominio.com',
    'https://www.seudominio.com',
    'http://localhost:8080',
    'http://127.0.0.1:8080'
  ].filter(Boolean);

  if (origin !== '*' && allowedOrigins.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
  } else if (origin === '*') {
    res.setHeader('Access-Control-Allow-Origin', '*');
  }

  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  res.setHeader('Access-Control-Max-Age', '86400');
}

export function handleCors(req, res, next) {
  const origin = req.headers.origin;
  setCorsHeaders(res, origin);

  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  if (next) next();
}
```

## 📄 Arquivo: `lib/whatsapp.js`

```javascript
import { makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@adiwajshing/baileys';
import QRCode from 'qrcode';
import NodeCache from 'node-cache';
import { v4 as uuidv4 } from 'uuid';

// Cache para sessões (em produção, use Redis)
const sessionsCache = new NodeCache({ stdTTL: 3600 }); // 1 hora
const qrCache = new NodeCache({ stdTTL: 300 }); // 5 minutos

export class WhatsAppManager {
  constructor() {
    this.sessions = new Map();
  }

  async createSession(sessionId = null) {
    try {
      if (!sessionId) {
        sessionId = `session-${uuidv4()}`;
      }

      // Verificar se sessão já existe
      if (this.sessions.has(sessionId)) {
        return {
          success: false,
          error: 'Session already exists',
          sessionId
        };
      }

      const { version } = await fetchLatestBaileysVersion();
      
      // Em produção, você precisará de um storage persistente
      // Por enquanto, usamos memória (será perdido no restart)
      const { state, saveCreds } = await useMultiFileAuthState(`./sessions/${sessionId}`);

      let qrCode = null;
      let isConnected = false;

      const sock = makeWASocket({
        version,
        auth: state,
        printQRInTerminal: false,
        browser: ['WhatsApp API', 'Chrome', '1.0.0']
      });

      // Eventos do socket
      sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
          try {
            qrCode = await QRCode.toDataURL(qr);
            qrCache.set(`qr-${sessionId}`, qrCode);
          } catch (error) {
            console.error('Error generating QR code:', error);
          }
        }

        if (connection === 'close') {
          const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
          
          if (shouldReconnect) {
            console.log('Reconnecting session:', sessionId);
            // Implementar lógica de reconexão
          } else {
            console.log('Session logged out:', sessionId);
            this.sessions.delete(sessionId);
            sessionsCache.del(sessionId);
          }
        } else if (connection === 'open') {
          console.log('Session connected:', sessionId);
          isConnected = true;
          sessionsCache.set(sessionId, {
            id: sessionId,
            status: 'connected',
            connectedAt: new Date().toISOString()
          });
        }
      });

      sock.ev.on('creds.update', saveCreds);

      // Armazenar sessão
      this.sessions.set(sessionId, {
        sock,
        isConnected: () => isConnected,
        getQR: () => qrCache.get(`qr-${sessionId}`),
        createdAt: new Date()
      });

      return {
        success: true,
        sessionId,
        qrCode: qrCode || 'Generating...',
        status: 'waiting_for_qr'
      };

    } catch (error) {
      console.error('Error creating session:', error);
      return {
        success: false,
        error: error.message,
        sessionId
      };
    }
  }

  getSession(sessionId) {
    return this.sessions.get(sessionId);
  }

  getSessionStatus(sessionId) {
    const session = this.sessions.get(sessionId);
    const cached = sessionsCache.get(sessionId);

    if (!session) {
      return {
        success: false,
        error: 'Session not found',
        status: 'not_found'
      };
    }

    return {
      success: true,
      sessionId,
      status: session.isConnected() ? 'connected' : 'disconnected',
      qrCode: session.getQR(),
      cached: cached || null
    };
  }

  async sendMessage(sessionId, to, message) {
    try {
      const session = this.sessions.get(sessionId);
      
      if (!session || !session.isConnected()) {
        return {
          success: false,
          error: 'Session not connected'
        };
      }

      // Formatar número
      const formattedNumber = to.includes('@') ? to : `${to}@s.whatsapp.net`;
      
      const result = await session.sock.sendMessage(formattedNumber, {
        text: message
      });

      return {
        success: true,
        messageId: result.key.id,
        to: formattedNumber,
        message
      };

    } catch (error) {
      return {
        success: false,
        error: error.message
      };
    }
  }

  deleteSession(sessionId) {
    try {
      const session = this.sessions.get(sessionId);
      
      if (session) {
        session.sock.end();
        this.sessions.delete(sessionId);
        sessionsCache.del(sessionId);
        qrCache.del(`qr-${sessionId}`);
      }

      return {
        success: true,
        message: 'Session deleted'
      };
    } catch (error) {
      return {
        success: false,
        error: error.message
      };
    }
  }

  listSessions() {
    const sessions = Array.from(this.sessions.keys()).map(sessionId => {
      const session = this.sessions.get(sessionId);
      return {
        sessionId,
        status: session.isConnected() ? 'connected' : 'disconnected',
        createdAt: session.createdAt
      };
    });

    return {
      success: true,
      sessions,
      total: sessions.length
    };
  }
}

// Instância global (em produção, use singleton pattern melhor)
export const whatsappManager = new WhatsAppManager();
```

## 📄 Arquivo: `api/sessions/index.js`

```javascript
import { whatsappManager } from '../../lib/whatsapp.js';
import { handleCors } from '../../lib/cors.js';

export default async function handler(req, res) {
  // Configurar CORS
  handleCors(req, res);

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  try {
    if (req.method === 'GET') {
      // Listar todas as sessões
      const result = whatsappManager.listSessions();
      return res.status(200).json(result);
    }

    if (req.method === 'POST') {
      // Criar nova sessão
      const { sessionId } = req.body || {};
      
      const result = await whatsappManager.createSession(sessionId);
      
      if (result.success) {
        return res.status(201).json(result);
      } else {
        return res.status(400).json(result);
      }
    }

    return res.status(405).json({
      success: false,
      error: 'Method not allowed'
    });

  } catch (error) {
    console.error('Sessions API Error:', error);
    return res.status(500).json({
      success: false,
      error: 'Internal server error',
      details: error.message
    });
  }
}
```

## 📄 Arquivo: `api/sessions/[sessionId].js`

```javascript
import { whatsappManager } from '../../lib/whatsapp.js';
import { handleCors } from '../../lib/cors.js';

export default async function handler(req, res) {
  handleCors(req, res);

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  const { sessionId } = req.query;

  if (!sessionId) {
    return res.status(400).json({
      success: false,
      error: 'Session ID is required'
    });
  }

  try {
    if (req.method === 'GET') {
      // Obter status da sessão
      const result = whatsappManager.getSessionStatus(sessionId);
      return res.status(200).json(result);
    }

    if (req.method === 'DELETE') {
      // Deletar sessão
      const result = whatsappManager.deleteSession(sessionId);
      return res.status(200).json(result);
    }

    return res.status(405).json({
      success: false,
      error: 'Method not allowed'
    });

  } catch (error) {
    console.error('Session API Error:', error);
    return res.status(500).json({
      success: false,
      error: 'Internal server error',
      details: error.message
    });
  }
}
```

## 📄 Arquivo: `api/chats/send.js`

```javascript
import { whatsappManager } from '../../lib/whatsapp.js';
import { handleCors } from '../../lib/cors.js';

export default async function handler(req, res) {
  handleCors(req, res);

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({
      success: false,
      error: 'Method not allowed'
    });
  }

  try {
    const { sessionId, to, message } = req.body;

    if (!sessionId || !to || !message) {
      return res.status(400).json({
        success: false,
        error: 'sessionId, to, and message are required'
      });
    }

    const result = await whatsappManager.sendMessage(sessionId, to, message);
    
    if (result.success) {
      return res.status(200).json(result);
    } else {
      return res.status(400).json(result);
    }

  } catch (error) {
    console.error('Send Message Error:', error);
    return res.status(500).json({
      success: false,
      error: 'Internal server error',
      details: error.message
    });
  }
}
```

## 📄 Arquivo: `api/test/qr.js`

```javascript
import { whatsappManager } from '../../lib/whatsapp.js';
import { handleCors } from '../../lib/cors.js';

export default async function handler(req, res) {
  handleCors(req, res);

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'GET') {
    return res.status(405).json({
      success: false,
      error: 'Method not allowed'
    });
  }

  try {
    // Criar sessão de teste
    const sessionId = `test-${Date.now()}`;
    const result = await whatsappManager.createSession(sessionId);

    return res.status(200).json({
      ...result,
      instructions: {
        scan: 'Scan the QR code with WhatsApp',
        check_status: `GET /api/test/status?sessionId=${sessionId}`,
        delete_session: `DELETE /api/sessions/${sessionId}`
      }
    });

  } catch (error) {
    console.error('Test QR Error:', error);
    return res.status(500).json({
      success: false,
      error: 'Internal server error',
      details: error.message
    });
  }
}
```

## 📄 Arquivo: `.env.example`

```env
# Frontend URL (Laravel)
FRONTEND_URL=https://seudominio.com

# API Configuration
NODE_ENV=production
API_SECRET=sua_chave_secreta_aqui

# WhatsApp Configuration
WA_SESSION_TIMEOUT=3600
WA_QR_TIMEOUT=300

# Redis (opcional, para cache persistente)
REDIS_URL=redis://localhost:6379

# Logs
LOG_LEVEL=info
```

## 🚀 Comandos de Deploy

```bash
# 1. Criar pasta do projeto
mkdir whatsapp-api-vercel
cd whatsapp-api-vercel

# 2. Copiar arquivos acima
# (criar todos os arquivos listados)

# 3. Instalar dependências
npm install

# 4. Testar localmente
vercel dev

# 5. Deploy para produção
vercel --prod

# 6. Configurar variáveis de ambiente
vercel env add FRONTEND_URL
vercel env add API_SECRET
vercel env add NODE_ENV
```

## 📋 Checklist de Arquivos

- [ ] `vercel.json` - Configuração do Vercel
- [ ] `package.json` - Dependências Node.js
- [ ] `lib/cors.js` - Configuração CORS
- [ ] `lib/whatsapp.js` - Manager do WhatsApp
- [ ] `api/sessions/index.js` - CRUD de sessões
- [ ] `api/sessions/[sessionId].js` - Operações por sessão
- [ ] `api/chats/send.js` - Envio de mensagens
- [ ] `api/test/qr.js` - Teste de QR code
- [ ] `.env.example` - Exemplo de variáveis

**Pronto! Com esses arquivos você pode fazer o deploy no Vercel! 🚀**