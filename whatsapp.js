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
            })

            sessions.set(sessionId, { ...sock, isLegacy })

            sock.ev.on('connection.update', async (update) => {
                const { connection, lastDisconnect, qr } = update
                
                if (qr) {
                    try {
                        const qrDataURL = await toDataURL(qr)
                        console.log('✅ QR code gerado com sucesso para sessão:', sessionId)
                        
                        if (res) {
                            response(res, 200, true, 'QR code generated', { qr: qrDataURL })
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
                    const shouldReconnectSession = shouldReconnect(sessionId)
                    
                    if (shouldReconnectSession) {
                        createSession(sessionId, isLegacy)
                    } else {
                        deleteSession(sessionId)
                    }
                } else if (connection === 'open') {
                    console.log(`Session ${sessionId} connected successfully`)
                }
            })

            sock.ev.on('creds.update', saveCreds)
            
            // Se não houver QR code em 10 segundos, considerar erro
            setTimeout(() => {
                if (!sock.user) {
                    reject({
                        success: false,
                        message: 'Timeout: QR code não foi gerado em 10 segundos'
                    })
                }
            }, 10000)
            
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
