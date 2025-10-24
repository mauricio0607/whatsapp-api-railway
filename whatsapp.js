import { rmSync, readdir, mkdirSync, existsSync } from 'fs'
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
    const dir = join(__dirname, 'sessions', sessionId ? sessionId : '')
    
    // Criar diretório se não existir
    if (!existsSync(dir)) {
        mkdirSync(dir, { recursive: true })
    }
    
    return dir
}

const isSessionExists = (sessionId) => {
    return sessions.has(sessionId)
}

const shouldReconnect = (sessionId) => {
    let maxRetries = parseInt(process.env.MAX_RETRIES ?? 0)
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

// Função para inicializar o sistema
const init = () => {
    console.log('WhatsApp Web.js system initialized')
    
    // Criar diretório de sessões se não existir
    const sessionsPath = sessionsDir()
    if (!existsSync(sessionsPath)) {
        mkdirSync(sessionsPath, { recursive: true })
        console.log('Sessions directory created:', sessionsPath)
    }
}

// Função para limpeza ao encerrar
const cleanup = () => {
    console.log('Cleaning up WhatsApp sessions...')
    
    // Fechar todas as sessões ativas
    sessions.forEach((session, sessionId) => {
        try {
            if (session && session.end) {
                session.end()
            }
        } catch (error) {
            console.error(`Error closing session ${sessionId}:`, error)
        }
    })
    
    sessions.clear()
    retries.clear()
    
    console.log('WhatsApp cleanup completed')
}

// Funções placeholder para manter compatibilidade
const createSession = (sessionId) => {
    console.log(`Creating session: ${sessionId}`)
    // Implementar lógica de criação de sessão aqui
    return { success: true, message: 'Session creation placeholder' }
}

const getSession = (sessionId) => {
    return sessions.get(sessionId)
}

const deleteSession = (sessionId) => {
    console.log(`Deleting session: ${sessionId}`)
    sessions.delete(sessionId)
    retries.delete(sessionId)
    return { success: true, message: 'Session deleted' }
}

const getChatList = (sessionId) => {
    console.log(`Getting chat list for: ${sessionId}`)
    return []
}

const isExists = (sessionId, jid) => {
    console.log(`Checking if exists: ${sessionId}, ${jid}`)
    return false
}

const sendMessage = (sessionId, jid, message) => {
    console.log(`Sending message: ${sessionId}, ${jid}`, message)
    return { success: true, message: 'Message sent placeholder' }
}

const formatPhone = (phone) => {
    return phone.replace(/\D/g, '') + '@s.whatsapp.net'
}

const formatGroup = (group) => {
    return group + '@g.us'
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
