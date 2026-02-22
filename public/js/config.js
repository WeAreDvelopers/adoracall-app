// ============================================
// CONFIGURAÇÃO DA API - BASE URL
// ============================================

const API_BASE_URL = window.location.origin + '/api';

// ============================================
// HELPER DE FETCH COM AUTENTICAÇÃO
// ============================================

/**
 * Faz uma requisição HTTP com autenticação via token API
 * Redireciona para login se o token não existir ou for inválido (401)
 */
async function fetchWithAuth(url, options = {}) {
    const token = localStorage.getItem('api_token');

    if (!token) {
        console.warn('⚠️ Token não encontrado no localStorage');
        // Redirect to login if no token
        if (!window.location.pathname.includes('/login')) {
            window.location.href = '/login';
        }
        throw new Error('Token não encontrado');
    }

    const headers = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...options.headers,
    };

    // Não definir Content-Type se o body for FormData (o navegador define automaticamente)
    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    // Super-admin: enviar header X-Empresa-Id se estiver impersonando uma empresa
    const empresaSelecionadaId = localStorage.getItem('empresa_selecionada_id');
    if (empresaSelecionadaId) {
        headers['X-Empresa-Id'] = empresaSelecionadaId;
    }

    console.debug('📤 Requisição:', { url, headers: { 'Authorization': `Bearer ${token.substring(0, 20)}...` } });

    const response = await fetch(url, {
        ...options,
        headers,
    });

    // Se 401, redirecionar para login
    if (response.status === 401) {
        console.error('❌ Erro 401 - Token inválido ou expirado');
        console.debug('Token armazenado:', token ? `${token.substring(0, 20)}...` : 'não encontrado');

        localStorage.removeItem('api_token');
        localStorage.removeItem('user');

        // Mostrar notificação ao usuário
        if (typeof showNotification === 'function') {
            showNotification('Sua sessão expirou. Faça login novamente.', 'error');
        }

        // Redirecionar para login após um pequeno delay
        setTimeout(() => {
            window.location.href = '/login';
        }, 1500);

        throw new Error('Não autorizado - faça login novamente');
    }

    return response;
}

/**
 * Debug helper - verificar token armazenado
 */
function debugToken() {
    const token = localStorage.getItem('api_token');
    const user = localStorage.getItem('user');
    console.log('🔍 Debug do Token:');
    console.log('Token encontrado:', !!token);
    if (token) {
        console.log('Token (primeiros 50 chars):', token.substring(0, 50) + '...');
        console.log('Comprimento do token:', token.length);
    }
    console.log('User:', user);
}

// ============================================
// CONFIGURAÇÃO DA API - RETELL (Legacy)
// ============================================

const API_CONFIG = {
    // URL do endpoint da API
    endpoint: 'https://carbonless-surlily-clorinda.ngrok-free.dev/api/ura/call/start',

    // Headers adicionais (se necessário)
    headers: {
        // Header necessário para ngrok
        'ngrok-skip-browser-warning': 'true',
        // Adicione headers customizados aqui se necessário
        // 'Authorization': 'Bearer YOUR_TOKEN',
        // 'X-Custom-Header': 'value'
    },

    // Timeout da requisição (em milissegundos)
    timeout: 30000,

    // Retry config
    retries: 0,
    retryDelay: 1000
};

const API_CONFIG_LOCAL = {
    // URL do endpoint da API
    endpoint: 'http://localhost:8000/api/ura/call/start',

    // Headers adicionais (se necessário)
    headers: {
        // Header necessário para ngrok
        'ngrok-skip-browser-warning': 'true',
        // Adicione headers customizados aqui se necessário
        // 'Authorization': 'Bearer YOUR_TOKEN',
        // 'X-Custom-Header': 'value'
    },

    // Timeout da requisição (em milissegundos)
    timeout: 30000,

    // Retry config
    retries: 0,
    retryDelay: 1000
};

// ============================================
// CONFIGURAÇÕES GERAIS
// ============================================

const APP_CONFIG = {
    // Nome da aplicação
    appName: 'Sistema de Cobrança CredMais',

    // Versão
    version: '1.0.0',

    // Configurações de histórico
    maxHistoryItems: 50,

    // Valores padrão
    defaults: {
        percentual_desconto: 10,
        max_parcelas: 3,
        max_parcelas_estendidas: 6
    }
};

// ============================================
// VALIDAÇÕES
// ============================================

const VALIDATION_CONFIG = {
    // Telefone - aceita formatos:
    // (11) 99999-9999
    // +5511999999999
    // 11999999999
    phoneRegex: /^(\+\d{2})?\s*\(?\d{2}\)?\s*\d{4,5}-?\d{4}$/,

    // Valor monetário - aceita formatos:
    // 425,00
    // 425.00
    // 425
    currencyRegex: /^\d+([.,]\d{1,2})?$/,

    // Limites
    limits: {
        maxParcelas: 24,
        maxParcelasEstendidas: 48,
        maxDesconto: 100,
        minDesconto: 0
    }
};

// ============================================
// EXPORT (se estiver usando módulos)
// ============================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        API_CONFIG,
        APP_CONFIG,
        VALIDATION_CONFIG
    };
}

// ============================================
// AUTO-REFRESH DE TOKEN (opcional)
// ============================================

/**
 * Tenta renovar o token antes de expirar
 * (Útil se o servidor suporta refresh)
 */
function setupTokenRefresh() {
    // Renovar token a cada 55 minutos (se TTL é 60 min)
    const refreshInterval = 55 * 60 * 1000;

    setInterval(() => {
        const token = localStorage.getItem('api_token');
        if (token && !window.location.pathname.includes('/login')) {
            // Você pode implementar um endpoint de refresh aqui
            console.log('🔄 Token refresh check (opcional)');
        }
    }, refreshInterval);
}

// Iniciar verificação de token se existir
if (localStorage.getItem('api_token') && !window.location.pathname.includes('/login')) {
    setupTokenRefresh();
}

// ============================================
// INFORMAÇÕES DE DEBUG
// ============================================

console.log('⚙️ Configuração carregada');
console.log('📍 API Base URL:', API_BASE_URL);
console.log('📦 Versão:', APP_CONFIG.version);
console.log('💡 Dica: Execute debugToken() no console para verificar o token');
