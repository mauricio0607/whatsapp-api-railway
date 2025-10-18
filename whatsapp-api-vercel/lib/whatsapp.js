import { makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@adiwajshing/baileys';
import QRCode from 'qrcode';
import NodeCache from 'node-cache';
import { v4 as uuidv4 } from 'uuid';
import pino from 'pino';

// Logger configurado para produção
const logger = pino({
  level: process.env.LOG_LEVEL || 'info',
  transport: process.env.NODE_ENV === 'development' ? {
    target: 'pino-pretty'
  } : undefined
});

// Cache para sessões (em produção, considere usar Redis)
const sessionsCache = new NodeCache({ stdTTL: 3600 }); // 1 hora
const qrCache = new NodeCache({ stdTTL: 300 }); // 5 minutos
const messageCache = new NodeCache({ stdTTL: 1800 }); // 30 minutos

export class WhatsAppManager {
  constructor() {
    this.sessions = new Map();
    this.connectionAttempts = new Map();
  }

  /**
   * Criar nova sessão WhatsApp
   */
  async createSession(sessionId = null) {
    try {
      if (!sessionId) {
        sessionId = `session-${uuidv4()}`;
      }

      // Verificar se sessão já existe
      if (this.sessions.has(sessionId)) {
        const existingSession = this.sessions.get(sessionId);
        return {
          success: false,
          error: 'Session already exists',
          sessionId,
          status: existingSession.isConnected() ? 'connected' : 'disconnected',
          qrCode: existingSession.getQR()
        };
      }

      logger.info(`Creating new WhatsApp session: ${sessionId}`);

      const { version } = await fetchLatestBaileysVersion();
      
      // Em ambiente serverless, usamos cache em memória
      // Para produção robusta, considere usar storage persistente
      const authState = {
        state: { creds: null, keys: null },
        saveCreds: () => {}
      };

      let qrCode = null;
      let isConnected = false;
      let connectionStatus = 'initializing';

      const sock = makeWASocket({
        version,
        auth: authState.state,
        printQRInTerminal: false,
        logger: logger.child({ sessionId }),
        browser: ['WhatsApp API Vercel', 'Chrome', '1.0.0'],
        connectTimeoutMs: 60000,
        defaultQueryTimeoutMs: 0,
        keepAliveIntervalMs: 10000,
        generateHighQualityLinkPreview: true,
        syncFullHistory: false,
        markOnlineOnConnect: true
      });

      // Eventos do socket
      sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        logger.info(`Session ${sessionId} connection update:`, { connection, qr: !!qr });

        if (qr) {
          try {
            qrCode = await QRCode.toDataURL(qr, {
              width: 256,
              margin: 2,
              color: {
                dark: '#000000',
                light: '#FFFFFF'
              }
            });
            qrCache.set(`qr-${sessionId}`, qrCode);
            connectionStatus = 'waiting_for_qr';
            
            logger.info(`QR Code generated for session: ${sessionId}`);
          } catch (error) {
            logger.error('Error generating QR code:', error);
          }
        }

        if (connection === 'close') {
          const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
          
          logger.warn(`Session ${sessionId} closed:`, {
            reason: lastDisconnect?.error?.output?.statusCode,
            shouldReconnect
          });

          if (shouldReconnect) {
            connectionStatus = 'reconnecting';
            // Em ambiente serverless, reconexão automática pode ser limitada
            logger.info(`Session ${sessionId} will attempt to reconnect`);
          } else {
            logger.info(`Session ${sessionId} logged out`);
            connectionStatus = 'logged_out';
            this.sessions.delete(sessionId);
            sessionsCache.del(sessionId);
            qrCache.del(`qr-${sessionId}`);
          }
        } else if (connection === 'open') {
          logger.info(`Session ${sessionId} connected successfully`);
          isConnected = true;
          connectionStatus = 'connected';
          qrCache.del(`qr-${sessionId}`); // Limpar QR code após conexão
          
          sessionsCache.set(sessionId, {
            id: sessionId,
            status: 'connected',
            connectedAt: new Date().toISOString(),
            user: sock.user
          });
        } else if (connection === 'connecting') {
          connectionStatus = 'connecting';
        }
      });

      sock.ev.on('creds.update', authState.saveCreds);

      // Eventos de mensagens (para logs e cache)
      sock.ev.on('messages.upsert', (m) => {
        const messages = m.messages;
        messages.forEach(msg => {
          if (msg.key.fromMe) {
            messageCache.set(`sent-${msg.key.id}`, {
              id: msg.key.id,
              to: msg.key.remoteJid,
              timestamp: msg.messageTimestamp,
              status: 'sent'
            });
          }
        });
      });

      // Armazenar sessão
      const sessionData = {
        sock,
        isConnected: () => isConnected,
        getQR: () => qrCache.get(`qr-${sessionId}`),
        getStatus: () => connectionStatus,
        createdAt: new Date(),
        lastActivity: new Date()
      };

      this.sessions.set(sessionId, sessionData);

      // Timeout para limpeza se não conectar em 5 minutos
      setTimeout(() => {
        if (!isConnected && this.sessions.has(sessionId)) {
          logger.warn(`Session ${sessionId} timeout - cleaning up`);
          this.deleteSession(sessionId);
        }
      }, 300000); // 5 minutos

      return {
        success: true,
        sessionId,
        qrCode: qrCode || null,
        status: connectionStatus,
        message: 'Session created successfully. Scan QR code to connect.',
        expiresIn: 300 // QR code expira em 5 minutos
      };

    } catch (error) {
      logger.error('Error creating session:', error);
      return {
        success: false,
        error: error.message,
        sessionId,
        details: process.env.NODE_ENV === 'development' ? error.stack : undefined
      };
    }
  }

  /**
   * Obter sessão existente
   */
  getSession(sessionId) {
    return this.sessions.get(sessionId);
  }

  /**
   * Obter status da sessão
   */
  getSessionStatus(sessionId) {
    const session = this.sessions.get(sessionId);
    const cached = sessionsCache.get(sessionId);

    if (!session) {
      return {
        success: false,
        error: 'Session not found',
        status: 'not_found',
        sessionId
      };
    }

    const qrCode = session.getQR();
    const status = session.getStatus();

    return {
      success: true,
      sessionId,
      status,
      isConnected: session.isConnected(),
      qrCode: qrCode || null,
      hasQR: !!qrCode,
      createdAt: session.createdAt,
      lastActivity: session.lastActivity,
      cached: cached || null,
      user: cached?.user || null
    };
  }

  /**
   * Enviar mensagem
   */
  async sendMessage(sessionId, to, message, options = {}) {
    try {
      const session = this.sessions.get(sessionId);
      
      if (!session || !session.isConnected()) {
        return {
          success: false,
          error: 'Session not connected',
          status: session ? session.getStatus() : 'not_found'
        };
      }

      // Atualizar última atividade
      session.lastActivity = new Date();

      // Formatar número de telefone
      const formattedNumber = this.formatPhoneNumber(to);
      
      logger.info(`Sending message from ${sessionId} to ${formattedNumber}`);

      // Preparar mensagem
      const messageData = {
        text: message
      };

      // Opções adicionais
      if (options.linkPreview !== undefined) {
        messageData.linkPreview = options.linkPreview;
      }

      const result = await session.sock.sendMessage(formattedNumber, messageData);

      // Cache da mensagem enviada
      const messageInfo = {
        messageId: result.key.id,
        sessionId,
        to: formattedNumber,
        message,
        timestamp: new Date().toISOString(),
        status: 'sent'
      };

      messageCache.set(`msg-${result.key.id}`, messageInfo);

      logger.info(`Message sent successfully:`, { 
        messageId: result.key.id, 
        to: formattedNumber 
      });

      return {
        success: true,
        messageId: result.key.id,
        to: formattedNumber,
        message,
        timestamp: messageInfo.timestamp,
        status: 'sent'
      };

    } catch (error) {
      logger.error('Error sending message:', error);
      return {
        success: false,
        error: error.message,
        details: process.env.NODE_ENV === 'development' ? error.stack : undefined
      };
    }
  }

  /**
   * Deletar sessão
   */
  deleteSession(sessionId) {
    try {
      const session = this.sessions.get(sessionId);
      
      if (session) {
        logger.info(`Deleting session: ${sessionId}`);
        
        // Fechar conexão
        if (session.sock) {
          session.sock.end();
        }
        
        // Limpar caches
        this.sessions.delete(sessionId);
        sessionsCache.del(sessionId);
        qrCache.del(`qr-${sessionId}`);
        
        // Limpar mensagens relacionadas
        const messageKeys = messageCache.keys().filter(key => 
          key.includes(sessionId)
        );
        messageKeys.forEach(key => messageCache.del(key));
      }

      return {
        success: true,
        message: 'Session deleted successfully',
        sessionId
      };
    } catch (error) {
      logger.error('Error deleting session:', error);
      return {
        success: false,
        error: error.message,
        sessionId
      };
    }
  }

  /**
   * Listar todas as sessões
   */
  listSessions() {
    try {
      const sessions = Array.from(this.sessions.keys()).map(sessionId => {
        const session = this.sessions.get(sessionId);
        const cached = sessionsCache.get(sessionId);
        
        return {
          sessionId,
          status: session.getStatus(),
          isConnected: session.isConnected(),
          hasQR: !!session.getQR(),
          createdAt: session.createdAt,
          lastActivity: session.lastActivity,
          user: cached?.user || null
        };
      });

      return {
        success: true,
        sessions,
        total: sessions.length,
        connected: sessions.filter(s => s.isConnected).length,
        timestamp: new Date().toISOString()
      };
    } catch (error) {
      logger.error('Error listing sessions:', error);
      return {
        success: false,
        error: error.message,
        sessions: [],
        total: 0
      };
    }
  }

  /**
   * Obter informações de uma mensagem
   */
  getMessageInfo(messageId) {
    const messageInfo = messageCache.get(`msg-${messageId}`);
    
    if (!messageInfo) {
      return {
        success: false,
        error: 'Message not found'
      };
    }

    return {
      success: true,
      ...messageInfo
    };
  }

  /**
   * Verificar se número existe no WhatsApp
   */
  async checkNumber(sessionId, phoneNumber) {
    try {
      const session = this.sessions.get(sessionId);
      
      if (!session || !session.isConnected()) {
        return {
          success: false,
          error: 'Session not connected'
        };
      }

      const formattedNumber = this.formatPhoneNumber(phoneNumber);
      const [result] = await session.sock.onWhatsApp(formattedNumber);

      return {
        success: true,
        exists: !!result?.exists,
        jid: result?.jid || null,
        phoneNumber: formattedNumber
      };

    } catch (error) {
      logger.error('Error checking number:', error);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Formatar número de telefone
   */
  formatPhoneNumber(phone) {
    // Remove caracteres especiais
    let cleaned = phone.replace(/[^0-9]/g, '');
    
    // Se já tem @s.whatsapp.net, retorna como está
    if (phone.includes('@s.whatsapp.net')) {
      return phone;
    }
    
    // Adiciona código do país se não tiver (Brasil = 55)
    if (cleaned.length === 11 && !cleaned.startsWith('55')) {
      cleaned = '55' + cleaned;
    }
    
    // Adiciona sufixo do WhatsApp
    return cleaned + '@s.whatsapp.net';
  }

  /**
   * Obter estatísticas do manager
   */
  getStats() {
    return {
      totalSessions: this.sessions.size,
      connectedSessions: Array.from(this.sessions.values()).filter(s => s.isConnected()).length,
      cachedSessions: sessionsCache.keys().length,
      cachedQRs: qrCache.keys().length,
      cachedMessages: messageCache.keys().length,
      uptime: process.uptime(),
      memory: process.memoryUsage(),
      timestamp: new Date().toISOString()
    };
  }

  /**
   * Limpeza de sessões inativas (para uso em cron jobs)
   */
  cleanupInactiveSessions(maxInactiveMinutes = 60) {
    const now = new Date();
    let cleaned = 0;

    for (const [sessionId, session] of this.sessions.entries()) {
      const inactiveMinutes = (now - session.lastActivity) / (1000 * 60);
      
      if (inactiveMinutes > maxInactiveMinutes && !session.isConnected()) {
        logger.info(`Cleaning up inactive session: ${sessionId}`);
        this.deleteSession(sessionId);
        cleaned++;
      }
    }

    return {
      success: true,
      cleanedSessions: cleaned,
      remainingSessions: this.sessions.size
    };
  }
}

// Instância global do manager
export const whatsappManager = new WhatsAppManager();

// Limpeza automática a cada 30 minutos
if (process.env.NODE_ENV === 'production') {
  setInterval(() => {
    whatsappManager.cleanupInactiveSessions(60);
  }, 30 * 60 * 1000); // 30 minutos
}

export default whatsappManager;