/**
 * API Endpoint: /api/chats/send
 * Envio de mensagens WhatsApp
 * 
 * POST /api/chats/send - Enviar mensagem (texto, imagem, documento, etc.)
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

    const { method, body } = req;

    if (method !== 'POST') {
      return sendErrorResponse(res, 405, 'Method not allowed', {
        allowedMethods: ['POST'],
        receivedMethod: method
      });
    }

    return await handleSendMessage(req, res, body);

  } catch (error) {
    console.error('Send Message API Error:', error);
    return sendErrorResponse(res, 500, 'Internal server error', {
      message: error.message,
      stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
}

/**
 * POST /api/chats/send
 * Enviar mensagem WhatsApp
 */
async function handleSendMessage(req, res, body) {
  try {
    // Validar entrada
    const validation = validateSendMessageInput(body || {});
    if (validation.errors.length > 0) {
      return sendErrorResponse(res, 400, 'Validation failed', {
        errors: validation.errors,
        received: body
      });
    }

    const {
      sessionId,        // ID da sessão
      to,              // Número de destino
      message,         // Conteúdo da mensagem
      type,            // Tipo: text, image, document, audio, video, location, contact
      options          // Opções adicionais
    } = body;

    // Verificar se sessão existe
    const session = whatsappManager.getSession(sessionId);
    if (!session) {
      return sendErrorResponse(res, 404, 'Session not found', {
        sessionId,
        message: 'The specified session does not exist',
        availableSessions: whatsappManager.listSessions().sessions.map(s => s.sessionId)
      });
    }

    // Verificar se sessão está conectada
    const status = whatsappManager.getSessionStatus(sessionId);
    if (!status.isConnected) {
      return sendErrorResponse(res, 400, 'Session not connected', {
        sessionId,
        currentStatus: status.status,
        message: 'Session must be connected to send messages'
      });
    }

    // Formatar número de destino
    const formattedNumber = formatPhoneNumber(to);
    if (!formattedNumber) {
      return sendErrorResponse(res, 400, 'Invalid phone number', {
        received: to,
        message: 'Phone number must be in format: +5511999999999 or 5511999999999'
      });
    }

    console.log(`Sending message:`, { 
      sessionId,
      to: formattedNumber,
      type: type || 'text',
      messageLength: typeof message === 'string' ? message.length : 'N/A'
    });

    // Preparar dados da mensagem
    const messageData = {
      to: formattedNumber,
      type: type || 'text',
      content: message,
      options: options || {}
    };

    // Enviar mensagem baseado no tipo
    let result;
    
    switch (messageData.type) {
      case 'text':
        result = await whatsappManager.sendTextMessage(sessionId, messageData);
        break;
      
      case 'image':
        result = await whatsappManager.sendImageMessage(sessionId, messageData);
        break;
      
      case 'document':
        result = await whatsappManager.sendDocumentMessage(sessionId, messageData);
        break;
      
      case 'audio':
        result = await whatsappManager.sendAudioMessage(sessionId, messageData);
        break;
      
      case 'video':
        result = await whatsappManager.sendVideoMessage(sessionId, messageData);
        break;
      
      case 'location':
        result = await whatsappManager.sendLocationMessage(sessionId, messageData);
        break;
      
      case 'contact':
        result = await whatsappManager.sendContactMessage(sessionId, messageData);
        break;
      
      case 'template':
        result = await whatsappManager.sendTemplateMessage(sessionId, messageData);
        break;
      
      default:
        return sendErrorResponse(res, 400, 'Unsupported message type', {
          type: messageData.type,
          supportedTypes: ['text', 'image', 'document', 'audio', 'video', 'location', 'contact', 'template']
        });
    }

    if (!result.success) {
      return sendErrorResponse(res, 400, 'Failed to send message', {
        error: result.error,
        sessionId,
        to: formattedNumber,
        type: messageData.type,
        details: result.details
      });
    }

    // Resposta de sucesso
    const response = {
      messageId: result.messageId,
      sessionId,
      to: formattedNumber,
      type: messageData.type,
      status: result.status,
      timestamp: result.timestamp,
      message: 'Message sent successfully',
      delivery: {
        sent: true,
        delivered: result.delivered || false,
        read: result.read || false
      },
      sentAt: new Date().toISOString()
    };

    // Incluir dados específicos do tipo de mensagem
    if (result.messageData) {
      response.messageData = result.messageData;
    }

    // Log de sucesso
    console.log(`Message sent successfully:`, {
      messageId: result.messageId,
      sessionId,
      to: formattedNumber,
      type: messageData.type,
      status: result.status
    });

    return sendSuccessResponse(res, response, 201);

  } catch (error) {
    console.error('Error sending message:', error);
    return sendErrorResponse(res, 500, 'Failed to send message', {
      message: error.message,
      stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
}

/**
 * Validação de entrada para envio de mensagem
 */
function validateSendMessageInput(body) {
  const errors = [];

  // Validar sessionId
  if (!body.sessionId) {
    errors.push('sessionId is required');
  } else if (typeof body.sessionId !== 'string') {
    errors.push('sessionId must be a string');
  }

  // Validar destinatário
  if (!body.to) {
    errors.push('to (phone number) is required');
  } else if (typeof body.to !== 'string') {
    errors.push('to must be a string');
  }

  // Validar mensagem
  if (!body.message && body.type !== 'location') {
    errors.push('message content is required');
  }

  // Validar tipo
  const validTypes = ['text', 'image', 'document', 'audio', 'video', 'location', 'contact', 'template'];
  if (body.type && !validTypes.includes(body.type)) {
    errors.push(`type must be one of: ${validTypes.join(', ')}`);
  }

  // Validações específicas por tipo
  if (body.type) {
    switch (body.type) {
      case 'text':
        if (typeof body.message !== 'string') {
          errors.push('message must be a string for text type');
        } else if (body.message.length > 4096) {
          errors.push('text message must be 4096 characters or less');
        }
        break;
      
      case 'image':
      case 'document':
      case 'audio':
      case 'video':
        if (typeof body.message !== 'string' && typeof body.message !== 'object') {
          errors.push(`message must be a string (URL/base64) or object (buffer/file) for ${body.type} type`);
        }
        break;
      
      case 'location':
        if (!body.message || typeof body.message !== 'object') {
          errors.push('message must be an object with latitude and longitude for location type');
        } else {
          if (typeof body.message.latitude !== 'number') {
            errors.push('latitude must be a number');
          }
          if (typeof body.message.longitude !== 'number') {
            errors.push('longitude must be a number');
          }
        }
        break;
      
      case 'contact':
        if (!body.message || typeof body.message !== 'object') {
          errors.push('message must be an object with contact information for contact type');
        } else {
          if (!body.message.name || typeof body.message.name !== 'string') {
            errors.push('contact name is required and must be a string');
          }
          if (!body.message.phone || typeof body.message.phone !== 'string') {
            errors.push('contact phone is required and must be a string');
          }
        }
        break;
      
      case 'template':
        if (!body.message || typeof body.message !== 'object') {
          errors.push('message must be an object with template information for template type');
        } else {
          if (!body.message.name || typeof body.message.name !== 'string') {
            errors.push('template name is required and must be a string');
          }
          if (!body.message.language || typeof body.message.language !== 'string') {
            errors.push('template language is required and must be a string');
          }
        }
        break;
    }
  }

  // Validar opções
  if (body.options !== undefined && typeof body.options !== 'object') {
    errors.push('options must be an object');
  }

  return { errors };
}

/**
 * Formatar número de telefone para o padrão WhatsApp
 */
function formatPhoneNumber(phone) {
  if (!phone || typeof phone !== 'string') {
    return null;
  }

  // Remover caracteres não numéricos exceto +
  let cleaned = phone.replace(/[^\d+]/g, '');

  // Remover + do início se existir
  if (cleaned.startsWith('+')) {
    cleaned = cleaned.substring(1);
  }

  // Verificar se tem pelo menos 10 dígitos
  if (cleaned.length < 10) {
    return null;
  }

  // Adicionar código do país se não tiver (assumir Brasil 55)
  if (cleaned.length === 10 || cleaned.length === 11) {
    cleaned = '55' + cleaned;
  }

  // Verificar formato final
  if (cleaned.length < 12 || cleaned.length > 15) {
    return null;
  }

  return cleaned + '@s.whatsapp.net';
}

/**
 * Validar se é um número de WhatsApp válido
 */
function isValidWhatsAppNumber(phone) {
  const formatted = formatPhoneNumber(phone);
  return formatted !== null;
}

/**
 * Preparar dados de mensagem baseado no tipo
 */
function prepareMessageData(type, content, options = {}) {
  const baseData = {
    type,
    timestamp: Date.now(),
    options
  };

  switch (type) {
    case 'text':
      return {
        ...baseData,
        text: content
      };
    
    case 'image':
      return {
        ...baseData,
        image: content,
        caption: options.caption || ''
      };
    
    case 'document':
      return {
        ...baseData,
        document: content,
        fileName: options.fileName || 'document',
        mimetype: options.mimetype || 'application/octet-stream'
      };
    
    case 'audio':
      return {
        ...baseData,
        audio: content,
        mimetype: options.mimetype || 'audio/ogg; codecs=opus',
        ptt: options.ptt || false
      };
    
    case 'video':
      return {
        ...baseData,
        video: content,
        caption: options.caption || '',
        mimetype: options.mimetype || 'video/mp4'
      };
    
    case 'location':
      return {
        ...baseData,
        location: {
          degreesLatitude: content.latitude,
          degreesLongitude: content.longitude,
          name: options.name || '',
          address: options.address || ''
        }
      };
    
    case 'contact':
      return {
        ...baseData,
        contacts: {
          displayName: content.name,
          contacts: [{
            name: {
              formattedName: content.name,
              firstName: content.firstName || content.name
            },
            phones: [{
              phone: content.phone,
              type: 'MOBILE'
            }],
            emails: content.email ? [{
              email: content.email,
              type: 'WORK'
            }] : []
          }]
        }
      };
    
    default:
      return baseData;
  }
}

/**
 * Middleware para rate limiting de mensagens
 */
const messageRateLimit = new Map();

function checkMessageRateLimit(sessionId, to) {
  const key = `${sessionId}:${to}`;
  const now = Date.now();
  const windowMs = 60000; // 1 minuto
  const maxMessages = 10; // máximo 10 mensagens por minuto por destinatário

  if (!messageRateLimit.has(key)) {
    messageRateLimit.set(key, { count: 1, resetTime: now + windowMs });
    return { allowed: true, remaining: maxMessages - 1 };
  }

  const limit = messageRateLimit.get(key);
  
  if (now > limit.resetTime) {
    // Reset do contador
    messageRateLimit.set(key, { count: 1, resetTime: now + windowMs });
    return { allowed: true, remaining: maxMessages - 1 };
  }

  if (limit.count >= maxMessages) {
    return { 
      allowed: false, 
      remaining: 0,
      resetTime: limit.resetTime 
    };
  }

  limit.count++;
  return { 
    allowed: true, 
    remaining: maxMessages - limit.count 
  };
}