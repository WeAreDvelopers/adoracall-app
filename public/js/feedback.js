/**
 * ============================================
 * SISTEMA DE FEEDBACK UNIFICADO
 * ============================================
 *
 * Classe FeedbackManager para exibir notificações
 * de sucesso, erro, aviso e informação de forma consistente
 */

class FeedbackManager {
    /**
     * Construtor do FeedbackManager
     * Cria o container de notificações se não existir
     */
    constructor() {
        this.createContainer();
        this.addStyles();
    }

    /**
     * Cria o container div para as notificações
     */
    createContainer() {
        if (document.getElementById('feedbackContainer')) return;

        const container = document.createElement('div');
        container.id = 'feedbackContainer';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px; width: 100%; pointer-events: none;';
        document.body.appendChild(container);
    }

    /**
     * Adiciona estilos CSS para as animações
     */
    addStyles() {
        if (document.getElementById('feedbackStyles')) return;

        const style = document.createElement('style');
        style.id = 'feedbackStyles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(450px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(450px);
                    opacity: 0;
                }
            }

            .feedback-alert {
                pointer-events: all;
            }

            .feedback-alert:hover {
                box-shadow: 0 6px 16px rgba(0,0,0,0.2) !important;
                transform: translateY(-2px) !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Exibe uma notificação
     *
     * @param {string} message - Mensagem a exibir
     * @param {string} type - Tipo de notificação (success, error, warning, info)
     * @param {number} duration - Duração em milissegundos (0 para não desaparecer)
     * @returns {HTMLElement} Elemento da notificação
     */
    show(message, type = 'info', duration = 5000) {
        const alert = document.createElement('div');
        alert.className = `feedback-alert feedback-${type}`;
        alert.style.cssText = `
            background: ${this.getColor(type)};
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
            cursor: pointer;
        `;

        const icon = document.createElement('i');
        icon.className = `fas fa-${this.getIcon(type)}`;
        icon.style.cssText = 'font-size: 20px; flex-shrink: 0;';

        const text = document.createElement('span');
        text.textContent = message;
        text.style.cssText = 'flex: 1;';

        const closeBtn = document.createElement('i');
        closeBtn.className = 'fas fa-times';
        closeBtn.style.cssText = 'cursor: pointer; flex-shrink: 0;';
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            alert.remove();
        });

        alert.addEventListener('click', () => {
            alert.remove();
        });

        alert.appendChild(icon);
        alert.appendChild(text);
        alert.appendChild(closeBtn);

        const container = document.getElementById('feedbackContainer');
        if (container) {
            container.appendChild(alert);
        }

        if (duration > 0) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            }, duration);
        }

        return alert;
    }

    /**
     * Exibe notificação de sucesso
     *
     * @param {string} message - Mensagem de sucesso
     * @param {number} duration - Duração em milissegundos
     * @returns {HTMLElement} Elemento da notificação
     */
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    /**
     * Exibe notificação de erro
     *
     * @param {string} message - Mensagem de erro
     * @param {number} duration - Duração em milissegundos
     * @returns {HTMLElement} Elemento da notificação
     */
    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    /**
     * Exibe notificação de aviso
     *
     * @param {string} message - Mensagem de aviso
     * @param {number} duration - Duração em milissegundos
     * @returns {HTMLElement} Elemento da notificação
     */
    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    /**
     * Exibe notificação de informação
     *
     * @param {string} message - Mensagem informativa
     * @param {number} duration - Duração em milissegundos
     * @returns {HTMLElement} Elemento da notificação
     */
    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    /**
     * Retorna a cor baseado no tipo de notificação
     *
     * @param {string} type - Tipo de notificação
     * @returns {string} Código hexadecimal da cor
     */
    getColor(type) {
        const colors = {
            success: '#4caf50',
            error: '#f44336',
            warning: '#ff9800',
            info: '#2196f3',
        };
        return colors[type] || colors.info;
    }

    /**
     * Retorna o ícone baseado no tipo de notificação
     *
     * @param {string} type - Tipo de notificação
     * @returns {string} Nome do ícone Font Awesome
     */
    getIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        return icons[type] || icons.info;
    }

    /**
     * Limpa todas as notificações
     */
    clearAll() {
        const container = document.getElementById('feedbackContainer');
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Criar instância global
window.feedback = new FeedbackManager();

console.log('📢 Sistema de Feedback carregado');
