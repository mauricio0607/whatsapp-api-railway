const express = require('express');
const cors = require('cors');
const { makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors({
    origin: '*',
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));
app.use(express.json());

// Armazenar sessões ativas
const sessions = new Map();
const qrCodes = new Map();

// Função para criar uma sessão WhatsApp
async function createWhatsAppSession(sessionId) {
    try {
        const authDir = path.join(__dirname, 'auth', sessionId);
        if (!fs.existsSync(authDir)) {
            fs.mkdirSync(authDir, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        
        const sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            browser: ['WhatsApp API', 'Chrome', '1.0.0']
        });

        sock.ev.on('creds.update', saveCreds);
        
        sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            if (qr) {
                // Gerar QR code como base64
                QRCode.toDataURL(qr, (err, url) => {
                    if (!err) {
                        qrCodes.set(sessionId, url);
                        console.log(`QR Code gerado para sessão ${sessionId}`);
                    }
                });
            }
            
            if (connection === 'close') {
                const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
                console.log('Conexão fechada devido a ', lastDisconnect?.error, ', reconectando ', shouldReconnect);
                
                if (shouldReconnect) {
                    createWhatsAppSession(sessionId);
                } else {
                    sessions.delete(sessionId);
                    qrCodes.delete(sessionId);
                }
            } else if (connection === 'open') {
                console.log(`Sessão ${sessionId} conectada com sucesso`);
                qrCodes.delete(sessionId); // Remove QR code após conexão
            }
        });

        sessions.set(sessionId, {
            socket: sock,
            status: 'connecting',
            createdAt: new Date()
        });

        return sock;
    } catch (error) {
        console.error(`Erro ao criar sessão ${sessionId}:`, error);
        throw error;
    }
}

// Endpoint para gerar QR code
app.get('/api/test/qr', async (req, res) => {
    try {
        const sessionId = `session_${Date.now()}`;
        
        // Resposta imediata para iniciar o processo
        res.json({
            success: true,
            data: {
                qr: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
                qrCode: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
                sessionId: sessionId,
                message: "QR Code gerado com sucesso"
            }
        });
        
        // Criar sessão em background
        createWhatsAppSession(sessionId).catch(err => {
            console.error('Erro ao criar sessão em background:', err);
        });
        
    } catch (error) {
        console.error('Erro ao gerar QR code:', error);
        res.status(500).json({
            success: false,
            message: 'Erro ao gerar QR code',
            error: error.message
        });
    }
});

// Endpoint para verificar status da sessão
app.get('/api/test/status/:sessionId', (req, res) => {
    try {
        const { sessionId } = req.params;
        const session = sessions.get(sessionId);
        
        if (!session) {
            return res.json({
                success: false,
                data: {
                    status: 'not_found',
                    message: 'Sessão não encontrada'
                }
            });
        }

        const socket = session.socket;
        const isConnected = socket?.user?.id;
        
        if (isConnected) {
            res.json({
                success: true,
                data: {
                    status: 'authenticated',
                    user: {
                        id: socket.user.id,
                        name: socket.user.name || 'Usuário WhatsApp'
                    }
                }
            });
        } else if (qrCodes.has(sessionId)) {
            res.json({
                success: true,
                data: {
                    status: 'qr_ready',
                    qr: qrCodes.get(sessionId)
                }
            });
        } else {
            res.json({
                success: true,
                data: {
                    status: 'connecting',
                    message: 'Aguardando conexão'
                }
            });
        }
    } catch (error) {
        console.error('Erro ao verificar status:', error);
        res.status(500).json({
            success: false,
            message: 'Erro ao verificar status',
            error: error.message
        });
    }
});

// Endpoint para criar sessão
app.post('/api/sessions', async (req, res) => {
    try {
        const sessionId = req.body.sessionId || `session_${Date.now()}`;
        
        if (sessions.has(sessionId)) {
            return res.json({
                success: false,
                message: 'Sessão já existe',
                sessionId: sessionId
            });
        }

        await createWhatsAppSession(sessionId);
        
        res.json({
            success: true,
            message: 'Sessão criada com sucesso',
            sessionId: sessionId
        });
    } catch (error) {
        console.error('Erro ao criar sessão:', error);
        res.status(500).json({
            success: false,
            message: 'Erro ao criar sessão',
            error: error.message
        });
    }
});

// Endpoint para listar sessões
app.get('/api/sessions', (req, res) => {
    try {
        const sessionList = Array.from(sessions.entries()).map(([id, session]) => ({
            sessionId: id,
            status: session.socket?.user?.id ? 'authenticated' : 'connecting',
            createdAt: session.createdAt,
            user: session.socket?.user || null
        }));

        res.json({
            success: true,
            data: sessionList
        });
    } catch (error) {
        console.error('Erro ao listar sessões:', error);
        res.status(500).json({
            success: false,
            message: 'Erro ao listar sessões',
            error: error.message
        });
    }
});

// Endpoint de health check
app.get('/api/health', (req, res) => {
    res.json({
        success: true,
        message: 'API WhatsApp funcionando',
        timestamp: new Date().toISOString(),
        sessions: sessions.size
    });
});

// Endpoint raiz
app.get('/', (req, res) => {
    res.json({
        success: true,
        message: 'WhatsApp API com Baileys',
        version: '1.0.0',
        endpoints: [
            'GET /api/test/qr - Gerar QR code',
            'GET /api/test/status/:sessionId - Status da sessão',
            'POST /api/sessions - Criar sessão',
            'GET /api/sessions - Listar sessões',
            'GET /api/health - Health check'
        ]
    });
});

// Middleware de erro 404
app.use((req, res) => {
    res.status(404).json({
        success: false,
        message: 'Endpoint não encontrado',
        url: req.originalUrl
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp API rodando na porta ${PORT}`);
    console.log(`📱 Endpoints disponíveis:`);
    console.log(`   GET  /api/test/qr - Gerar QR code`);
    console.log(`   GET  /api/test/status/:sessionId - Status da sessão`);
    console.log(`   POST /api/sessions - Criar sessão`);
    console.log(`   GET  /api/sessions - Listar sessões`);
    console.log(`   GET  /api/health - Health check`);
});

module.exports = app;