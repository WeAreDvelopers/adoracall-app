/**
 * Auth Interceptor
 * Adiciona o token JWT do localStorage ao header Authorization
 * de todas as requisições para o servidor
 */

(function() {
    // Armazenar o fetch original
    const originalFetch = window.fetch;

    // Substituir fetch global
    window.fetch = function(...args) {
        const url = args[0];
        const options = args[1] || {};

        // Apenas adicionar token para requisições para o mesmo domínio
        if (typeof url === 'string' && (url.startsWith('/') || url.includes(window.location.origin))) {
            const token = localStorage.getItem('api_token');

            if (token) {
                // Inicializar headers se não existir
                if (!options.headers) {
                    options.headers = {};
                }

                // Adicionar o token no header Authorization
                options.headers['Authorization'] = `Bearer ${token}`;
            }
        }

        // Chamar o fetch original com os argumentos modificados
        return originalFetch.apply(this, [url, options]);
    };

    // Copiar propriedades do fetch original para o novo
    Object.setPrototypeOf(window.fetch, originalFetch);
})();
