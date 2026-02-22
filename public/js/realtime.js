/**
 * Gerenciador de conexão em tempo real
 * Implementa fallback de polling se SSE não disponível
 */

class RealtimeManager {
    constructor(apiUrl = 'http://localhost:8080/api') {
        this.apiUrl = apiUrl;
        this.eventSource = null;
        this.pollingInterval = null;
        this.callbacks = {};
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.usePolling = false;
    }

    /**
     * Conecta ao servidor (tenta SSE primeiro, depois polling)
     */
    conectar() {
        // Tentar SSE primeiro
        if (this.tentarSSE()) {
            return true;
        }

        // Fallback para polling
        console.log('SSE não disponível, usando polling');
        this.usePolling = true;
        return this.iniciarPolling();
    }

    /**
     * Tenta conectar com Server-Sent Events
     */
    tentarSSE() {
        try {
            // Verificar suporte a SSE
            if (!window.EventSource) {
                console.warn('EventSource não suportado');
                return false;
            }

            this.eventSource = new EventSource(`${this.apiUrl}/eventos-tempo-real`);

            this.eventSource.addEventListener('fila-atualizada', (e) => {
                const dados = JSON.parse(e.data);
                this.executarCallback('fila-atualizada', dados);
            });

            this.eventSource.addEventListener('mailing-atualizado', (e) => {
                const dados = JSON.parse(e.data);
                this.executarCallback('mailing-atualizado', dados);
            });

            this.eventSource.addEventListener('job-completado', (e) => {
                const dados = JSON.parse(e.data);
                this.executarCallback('job-completado', dados);
            });

            this.eventSource.addEventListener('job-falhou', (e) => {
                const dados = JSON.parse(e.data);
                this.executarCallback('job-falhou', dados);
            });

            this.eventSource.onerror = () => {
                this.desconectar();
                this.tentarReconectar();
            };

            console.log('SSE conectado com sucesso');
            this.reconnectAttempts = 0;
            return true;
        } catch (error) {
            console.error('Erro ao conectar SSE:', error);
            return false;
        }
    }

    /**
     * Inicia polling como fallback
     */
    iniciarPolling(intervalo = 3000) {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        // Polling imediato
        this.fazerPolling();

        // Polling periódico
        this.pollingInterval = setInterval(() => {
            this.fazerPolling();
        }, intervalo);

        console.log('Polling iniciado com intervalo de ' + intervalo + 'ms');
        return true;
    }

    /**
     * Faz uma chamada de polling
     */
    fazerPolling() {
        // Buscar dados de filas
        Promise.all([
            fetch(`${this.apiUrl}/queues/stats`).then(r => r.json()),
            fetch(`${this.apiUrl}/filas_campanha`).then(r => r.json())
        ])
        .then(([statsResposta, mailingsResposta]) => {
            if (statsResposta.data) {
                this.executarCallback('fila-atualizada', statsResposta.data);
            }
            if (mailingsResposta.data) {
                this.executarCallback('mailings-atualizadas', mailingsResposta.data);
            }
        })
        .catch(error => console.error('Erro no polling:', error));
    }

    /**
     * Registra callback para um tipo de evento
     */
    on(evento, callback) {
        if (!this.callbacks[evento]) {
            this.callbacks[evento] = [];
        }
        this.callbacks[evento].push(callback);
    }

    /**
     * Remove callback
     */
    off(evento, callback) {
        if (this.callbacks[evento]) {
            this.callbacks[evento] = this.callbacks[evento].filter(cb => cb !== callback);
        }
    }

    /**
     * Executa callbacks registrados
     */
    executarCallback(evento, dados) {
        if (this.callbacks[evento]) {
            this.callbacks[evento].forEach(callback => {
                try {
                    callback(dados);
                } catch (error) {
                    console.error(`Erro ao executar callback de ${evento}:`, error);
                }
            });
        }
    }

    /**
     * Tenta reconectar
     */
    tentarReconectar() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Máximo de tentativas de reconexão atingido');
            return false;
        }

        this.reconnectAttempts++;
        console.log(`Reconectando... (tentativa ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

        setTimeout(() => {
            this.conectar();
        }, this.reconnectDelay * this.reconnectAttempts);

        return true;
    }

    /**
     * Desconecta
     */
    desconectar() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }

        console.log('Desconectado');
    }

    /**
     * Verifica se está conectado
     */
    estaConectado() {
        return (this.eventSource !== null && this.eventSource.readyState === EventSource.OPEN) ||
               (this.usePolling && this.pollingInterval !== null);
    }

    /**
     * Retorna status da conexão
     */
    obterStatus() {
        return {
            conectado: this.estaConectado(),
            tipo: this.usePolling ? 'polling' : 'sse',
            tentativasReconexao: this.reconnectAttempts
        };
    }
}

// Instância global
const realtime = new RealtimeManager();
