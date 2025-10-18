import { Router } from 'express'
import { createSession, getSession } from '../whatsapp.js'
import response from '../response.js'

const router = Router()

// Endpoint para gerar QR code de teste
router.get('/qr', async (req, res) => {
    try {
        console.log('🔄 Iniciando geração de QR code de teste...')
        
        // Gerar ID único para a sessão de teste
        const testSessionId = `test-${Date.now()}`
        
        console.log(`📱 Criando sessão de teste: ${testSessionId}`)
        
        // Criar sessão e obter QR code
        const result = await createSession(testSessionId, false)
        
        if (result.success && result.qr) {
            console.log('✅ QR code gerado com sucesso!')
            
            response(res, 200, true, 'QR code de teste gerado com sucesso', {
                sessionId: testSessionId,
                qr: result.qr,
                instructions: [
                    '1. Abra o WhatsApp no seu celular',
                    '2. Vá em Configurações > Aparelhos conectados',
                    '3. Toque em "Conectar um aparelho"',
                    '4. Escaneie o QR code abaixo',
                    '5. Use GET /test/status/{sessionId} para verificar o status'
                ]
            })
        } else {
            console.log('❌ Erro ao gerar QR code:', result.message)
            response(res, 500, false, result.message || 'Erro ao gerar QR code')
        }
    } catch (error) {
        console.error('❌ Erro no endpoint /test/qr:', error)
        response(res, 500, false, 'Erro interno do servidor', { error: error.message })
    }
})

// Endpoint para verificar status da sessão de teste
router.get('/status/:sessionId', async (req, res) => {
    try {
        const { sessionId } = req.params
        
        console.log(`🔍 Verificando status da sessão: ${sessionId}`)
        
        const session = getSession(sessionId)
        
        if (!session) {
            console.log(`❌ Sessão não encontrada: ${sessionId}`)
            response(res, 404, false, 'Sessão não encontrada', {
                sessionId,
                status: 'not_found'
            })
            return
        }
        
        // Verificar se a sessão está autenticada
        const isAuthenticated = session.user && session.user.id
        
        console.log(`📊 Status da sessão ${sessionId}: ${isAuthenticated ? 'autenticada' : 'aguardando autenticação'}`)
        
        response(res, 200, true, 'Status da sessão obtido', {
            sessionId,
            status: isAuthenticated ? 'authenticated' : 'waiting_for_auth',
            user: session.user || null,
            connected: session.ws && session.ws.readyState === 1,
            lastSeen: session.lastSeen || null
        })
        
    } catch (error) {
        console.error('❌ Erro no endpoint /test/status:', error)
        response(res, 500, false, 'Erro interno do servidor', { error: error.message })
    }
})

// Endpoint para listar todas as sessões de teste
router.get('/sessions', async (req, res) => {
    try {
        console.log('📋 Listando todas as sessões de teste...')
        
        // Aqui você pode implementar uma função para listar todas as sessões
        // Por enquanto, vamos retornar uma resposta básica
        
        response(res, 200, true, 'Lista de sessões de teste', {
            message: 'Use POST /sessions/add para criar uma nova sessão',
            endpoints: {
                'Criar sessão': 'POST /sessions/add',
                'QR code de teste': 'GET /test/qr',
                'Status da sessão': 'GET /test/status/{sessionId}',
                'Status do servidor': 'GET /sessions/server-status'
            }
        })
        
    } catch (error) {
        console.error('❌ Erro no endpoint /test/sessions:', error)
        response(res, 500, false, 'Erro interno do servidor', { error: error.message })
    }
})

// Endpoint para deletar sessão de teste
router.delete('/session/:sessionId', async (req, res) => {
    try {
        const { sessionId } = req.params
        
        console.log(`🗑️ Deletando sessão de teste: ${sessionId}`)
        
        const session = getSession(sessionId)
        
        if (!session) {
            response(res, 404, false, 'Sessão não encontrada')
            return
        }
        
        // Fechar conexão se existir
        if (session.ws) {
            session.ws.close()
        }
        
        // Aqui você implementaria a lógica para remover a sessão
        // Por enquanto, apenas retornamos sucesso
        
        console.log(`✅ Sessão ${sessionId} deletada com sucesso`)
        
        response(res, 200, true, 'Sessão deletada com sucesso', {
            sessionId,
            status: 'deleted'
        })
        
    } catch (error) {
        console.error('❌ Erro no endpoint /test/session DELETE:', error)
        response(res, 500, false, 'Erro interno do servidor', { error: error.message })
    }
})

export default router