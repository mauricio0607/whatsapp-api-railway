import { Router } from 'express'
import sessionsRoute from './routes/sessionsRoute.js'
import chatsRoute from './routes/chatsRoute.js'
import groupsRoute from './routes/groupsRoute.js'
import testRoute from './routes/testRoute.js'
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
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    })
})

router.use('/sessions', sessionsRoute)
router.use('/chats', chatsRoute)
router.use('/groups', groupsRoute)
router.use('/test', testRoute)

router.all('*', (req, res) => {
    response(res, 404, false, 'The requested url cannot be found.')
})

export default router
