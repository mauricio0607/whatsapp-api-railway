import { Router } from 'express'
import response from './response.js'

const router = Router()

// Health check endpoint for Railway
router.get('/', (req, res) => {
    res.json({
        status: 'ok',
        message: 'WhatsApp API is running',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    })
})

// Health check endpoint
router.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        service: 'WhatsApp API',
        timestamp: new Date().toISOString()
    })
})

// Test endpoint
router.get('/test', (req, res) => {
    res.json({
        message: 'API is working!',
        timestamp: new Date().toISOString()
    })
})

// QR Code test endpoint
router.get('/test/qr', (req, res) => {
    res.json({
        message: 'QR endpoint ready',
        instructions: 'Use POST /sessions/add to create a session and get QR code',
        timestamp: new Date().toISOString()
    })
})

// Status test endpoint
router.get('/test/status', (req, res) => {
    res.json({
        message: 'Status endpoint ready',
        instructions: 'Use GET /sessions/status/:id to check session status',
        timestamp: new Date().toISOString()
    })
})

export default router