import { rmSync, readdir } from 'fs'
import { join } from 'path'
import pino from 'pino'
import makeWASocket, {
    useMultiFileAuthState,
    Browsers,
    DisconnectReason,
    delay,
} from '@adiwajshing/baileys'
import { toDataURL } from 'qrcode'
import __dirname from './dirname.js'
import response from './response.js'
import axios from 'axios';

const sessions = new Map()
const retries = new Map()

const sessionsDir = (sessionId = '') => {
    return join(__dirname, 'sessions', sessionId ? sessionId : '')
}

const isSessionExists = (sessionId) => {
    return sessions.has(sessionId)
}

const shouldReconnect = (sessionId) => {
    let maxRetries = parseInt(process.env.MAX_RETRIES ?? 5)
    let attempts = retries.get(sessionId) ?? 0

    maxRetries = maxRetries < 1 ? 1 : maxRetries

    if (attempts < maxRetries) {
        ++attempts
        console.log('Reconnecting...', { attempts, sessionId })
        retries.set(sessionId, attempts)
        return true
    }

    return false
}

const createSession = async (sessionId, isLegacy = false, res = null) => {
    console.log('Creating session:', sessionId)
    
    return new Promise(async (resolve, reject) => {
        try {
            const sessionDir = sessionsDir(sessionId)
            const { state, saveCreds } = await useMultiFileAuthState(sessionDir)
            
            const sock = makeWASocket({
                auth: state,
                printQRInTerminal: true,
                logger: pino({ level: 'silent' }),
                browser: Browsers.ubuntu('Chrome'),
                connectTimeoutMs: 60000,
                defaultQueryTimeoutMs: 0,
                keepAliveIntervalMs: 10000,
                markOnlineOnConnect: true,
                syncFullHistory: false,
                fireInitQueries: true,
                generateHighQualityLinkPreview: false,
                patchMessageBeforeSending: (message) => {
                    const requiresPatch = !!(
                        message.buttonsMessage ||
                        message.templateMessage ||
                        message.listMessage
                    );
                    if (requiresPatch) {
                        message = {
                            viewOnceMessage: {
                                message: {
                                    messageContextInfo: {
                                        deviceListMetadataVersion: 2,
                                        deviceListMetadata: {},
                                    },
                                    ...message,
                                },
                            },
                        };
                    }
                    return message;
                },
            })

            sessions.set(sessionId, { ...sock, isLegacy })

            sock.ev.on('connection.update', async (update) => {
                const { connection, lastDisconnect, qr } = update
                
                console.log(`🔄 Connection update for ${sessionId}:`, { connection, qr: !!qr })
                
                if (qr) {
                    try {
                        const qrDataURL = await toDataURL(qr, { 
                            errorCorrectionLevel: 'M',
                            type: 'image/png',
                            quality: 0.92,
                            margin: 1,
                            color: {
                                dark: '#000000',
                                light: '#FFFFFF'
                            }
                        })
                        console.log('✅ QR code gerado com sucesso para sessão:', sessionId)
                        console.log('📱 QR Code válido por 60 segundos. Escaneie rapidamente!')
                        
                        if (res) {
                            response(res, 200, true, 'QR code generated', { 
                                qr: qrDataURL,
                                sessionId,
                                expiresIn: 60,
                                instructions: [
                                    '1. Abra o WhatsApp no seu celular',
                                    '2. Vá em Configurações > Aparelhos conectados',
                                    '3. Toque em "Conectar um aparelho"',
                                    '4. Escaneie o QR code rapidamente (60s)',
                                    '5. Aguarde a confirmação de conexão'
                                ]
                            })
                        }
                        
                        resolve({
                            success: true,
                            sessionId,
                            qr: qrDataURL,
                            message: 'QR code generated successfully'
                        })
                    } catch (qrError) {
                        console.error('❌ Erro ao gerar QR code:', qrError)
                        reject({
                            success: false,
                            message: 'Erro ao gerar QR code',
                            error: qrError.message
                        })
                    }
                }
                
                if (connection === 'close') {
                    const statusCode = lastDisconnect?.error?.output?.statusCode
                    console.log(`🔌 Conexão fechada para ${sessionId}. Status:`, statusCode)
                    
                    const shouldReconnectSession = (
                        statusCode !== DisconnectReason.loggedOut &&
                        statusCode !== DisconnectReason.forbidden &&
                        shouldReconnect(sessionId)
                    )
                    
                    if (shouldReconnectSession) {
                        console.log(`🔄 Tentando reconectar sessão ${sessionId}...`)
                        setTimeout(() => createSession(sessionId, isLegacy), 3000)
                    } else {
                        console.log(`❌ Sessão ${sessionId} encerrada definitivamente`)
                        deleteSession(sessionId)
                    }
                } else if (connection === 'open') {
                    console.log(`✅ Sessão ${sessionId} conectada com sucesso!`)
                    retries.delete(sessionId)
                } else if (connection === 'connecting') {
                    console.log(`🔄 Conectando sessão ${sessionId}...`)
                }
            })

            sock.ev.on('creds.update', saveCreds)
            
            // Se não houver QR code em 30 segundos, considerar erro
            setTimeout(() => {
                if (!sock.user) {
                    console.log(`⏰ Timeout para sessão ${sessionId} - QR code não foi gerado`)
                    reject({
                        success: false,
                        message: 'Timeout: QR code não foi gerado em 30 segundos'
                    })
                }
            }, 30000)
            
        } catch (error) {
            console.error('❌ Erro ao criar sessão:', error)
            reject({
                success: false,
                message: 'Erro ao criar sessão',
                error: error.message
            })
        }
    })
}

const getSession = (sessionId) => {
    return sessions.get(sessionId)
}

const deleteSession = (sessionId) => {
    sessions.delete(sessionId)
    retries.delete(sessionId)
    return true
}

const getChatList = (sessionId, isGroup = false) => {
    const session = getSession(sessionId)
    if (!session) return []
    // Implementação básica para obter lista de chats
    return []
}

const isExists = async (session, jid, isGroup = false) => {
    try {
        if (isGroup) {
            const groupMeta = await session.groupMetadata(jid)
            return !!groupMeta.id
        }
        const [result] = await session.onWhatsApp(jid)
        return result?.exists || false
    } catch {
        return false
    }
}

const sendMessage = async (session, jid, message, delay = 0) => {
    if (delay > 0) {
        await new Promise(resolve => setTimeout(resolve, delay))
    }
    return await session.sendMessage(jid, message)
}

const formatPhone = (phone) => {
    if (phone.endsWith('@s.whatsapp.net')) {
        return phone
    }
    let formatted = phone.replace(/\D/g, '')
    return formatted + '@s.whatsapp.net'
}

const formatGroup = (group) => {
    if (group.endsWith('@g.us')) {
        return group
    }
    return group + '@g.us'
}

const cleanup = () => {
    console.log('Cleaning up sessions...')
    sessions.clear()
    retries.clear()
}

const init = () => {
    console.log('Initializing WhatsApp service...')
    // Implementação básica de inicialização
}


export {
    isSessionExists,
    createSession,
    getSession,
    deleteSession,
    getChatList,
    isExists,
    sendMessage,
    formatPhone,
    formatGroup,
    cleanup,
    init,
}
