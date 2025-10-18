export default function handler(req, res) {
  // Configurar CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  // Responder a OPTIONS (preflight)
  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  // Resposta simples para teste
  const response = {
    status: 'success',
    message: 'API WhatsApp funcionando!',
    timestamp: new Date().toISOString(),
    method: req.method,
    url: req.url
  };

  res.status(200).json(response);
}