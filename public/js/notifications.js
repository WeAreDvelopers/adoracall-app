/**
 * Gerenciador de notificações do navegador
 * Pede permissão para notificações e as exibe quando eventos importantes ocorrem
 */

class NotificacaoManager {
    constructor() {
        this.permissao = false;
        this.historico = [];
        this.soundEnabled = true;
    }

    /**
     * Solicita permissão para notificações
     */
    solicitarPermissao() {
        if (!('Notification' in window)) {
            console.log('Navegador não suporta notificações');
            return false;
        }

        if (Notification.permission === 'granted') {
            this.permissao = true;
            return true;
        }

        if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                this.permissao = permission === 'granted';
            });
        }

        return false;
    }

    /**
     * Mostra notificação
     */
    mostrar(titulo, opcoes = {}) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            console.log('Notificações não disponíveis');
            return false;
        }

        const notificacao = new Notification(titulo, {
            icon: 'https://cdn-icons-png.flaticon.com/512/2997/2997495.png',
            badge: 'https://cdn-icons-png.flaticon.com/512/2997/2997495.png',
            ...opcoes
        });

        // Auto-fechar após 5 segundos
        if (opcoes.autofecha !== false) {
            setTimeout(() => notificacao.close(), 5000);
        }

        // Soar notificação
        if (this.soundEnabled) {
            this.tocarSom();
        }

        // Registrar no histórico
        this.historico.push({
            titulo,
            opcoes,
            tempo: new Date()
        });

        return notificacao;
    }

    /**
     * Notificação de sucesso
     */
    sucesso(titulo, mensagem = '') {
        return this.mostrar(titulo, {
            body: mensagem,
            tag: 'sucesso'
        });
    }

    /**
     * Notificação de aviso
     */
    aviso(titulo, mensagem = '') {
        return this.mostrar(titulo, {
            body: mensagem,
            tag: 'aviso'
        });
    }

    /**
     * Notificação de erro
     */
    erro(titulo, mensagem = '') {
        return this.mostrar(titulo, {
            body: mensagem,
            tag: 'erro'
        });
    }

    /**
     * Notificação de informação
     */
    info(titulo, mensagem = '') {
        return this.mostrar(titulo, {
            body: mensagem,
            tag: 'info'
        });
    }

    /**
     * Toca som de notificação
     */
    tocarSom() {
        try {
            // Som simples usando Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.log('Erro ao tocar som:', error);
        }
    }

    /**
     * Ativa/desativa sons
     */
    alternarSom(ativo = null) {
        if (ativo !== null) {
            this.soundEnabled = ativo;
        } else {
            this.soundEnabled = !this.soundEnabled;
        }
        return this.soundEnabled;
    }

    /**
     * Obter histórico
     */
    obterHistorico(limite = 10) {
        return this.historico.slice(-limite);
    }

    /**
     * Limpar histórico
     */
    limparHistorico() {
        this.historico = [];
    }
}

// Instância global
const notificacoes = new NotificacaoManager();

// Solicitar permissão ao carregar
document.addEventListener('DOMContentLoaded', function() {
    notificacoes.solicitarPermissao();
});
