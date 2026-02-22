/**
 * ============================================
 * BIBLIOTECA DE VALIDAÇÃO UNIFICADA
 * ============================================
 *
 * Classe ValidationManager para validação de formulários
 * com suporte a validação em tempo real e mensagens customizadas
 */

class ValidationManager {
    /**
     * Construtor do ValidationManager
     *
     * @param {string} formId - ID do formulário a ser validado
     * @param {Object} options - Opções de configuração
     * @param {Object} options.rules - Regras de validação para cada campo
     * @param {Object} options.messages - Mensagens customizadas de erro
     * @param {Function} options.onSuccess - Callback ao validar com sucesso
     * @param {Function} options.onError - Callback ao validar com erro
     */
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);

        if (!this.form) {
            console.error(`Formulário com ID "${formId}" não encontrado`);
            return;
        }

        this.rules = options.rules || {};
        this.messages = options.messages || {};
        this.onSuccess = options.onSuccess || (() => {});
        this.onError = options.onError || (() => {});

        this.attachListeners();
    }

    /**
     * Anexa event listeners ao formulário
     */
    attachListeners() {
        // Validação no submit
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.validate();
        });

        // Validação em tempo real (on blur)
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });

            // Remove erro ao começar a digitar
            field.addEventListener('input', () => {
                this.clearFieldError(field);
            });

            field.addEventListener('change', () => {
                this.clearFieldError(field);
            });
        });
    }

    /**
     * Valida todo o formulário
     *
     * @returns {boolean} True se válido, false caso contrário
     */
    validate() {
        let valid = true;
        const errors = {};

        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            const fieldValid = this.validateField(field);

            if (!fieldValid) {
                valid = false;
                errors[fieldName] = this.getErrorMessage(fieldName);
            }
        });

        if (valid) {
            this.onSuccess(this.getFormData());
        } else {
            this.onError(errors);
        }

        return valid;
    }

    /**
     * Valida um campo individual
     *
     * @param {HTMLElement} field - Campo a ser validado
     * @returns {boolean} True se válido, false caso contrário
     */
    validateField(field) {
        if (!field) return true;

        const rules = this.rules[field.name];
        if (!rules) return true;

        const value = field.value;

        // Required
        if (rules.required && !value) {
            this.showFieldError(field, this.messages[field.name]?.required || 'Campo obrigatório');
            return false;
        }

        if (!value) {
            this.clearFieldError(field);
            return true;
        }

        // Email
        if (rules.email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showFieldError(field, this.messages[field.name]?.email || 'E-mail inválido');
                return false;
            }
        }

        // Min length
        if (rules.minLength && value.length < rules.minLength) {
            this.showFieldError(field, this.messages[field.name]?.minLength || `Mínimo ${rules.minLength} caracteres`);
            return false;
        }

        // Max length
        if (rules.maxLength && value.length > rules.maxLength) {
            this.showFieldError(field, this.messages[field.name]?.maxLength || `Máximo ${rules.maxLength} caracteres`);
            return false;
        }

        // Min value
        if (rules.min !== undefined && parseFloat(value) < parseFloat(rules.min)) {
            this.showFieldError(field, this.messages[field.name]?.min || `Valor mínimo: ${rules.min}`);
            return false;
        }

        // Max value
        if (rules.max !== undefined && parseFloat(value) > parseFloat(rules.max)) {
            this.showFieldError(field, this.messages[field.name]?.max || `Valor máximo: ${rules.max}`);
            return false;
        }

        // CPF
        if (rules.cpf) {
            if (!this.validateCPF(value)) {
                this.showFieldError(field, this.messages[field.name]?.cpf || 'CPF inválido');
                return false;
            }
        }

        // Telefone
        if (rules.phone) {
            const phoneRegex = /^(\+\d{2})?\s*\(?\d{2}\)?\s*\d{4,5}-?\d{4}$/;
            if (!phoneRegex.test(value)) {
                this.showFieldError(field, this.messages[field.name]?.phone || 'Telefone inválido');
                return false;
            }
        }

        // Custom validation
        if (rules.custom && typeof rules.custom === 'function') {
            const customResult = rules.custom(value, this.getFormData());
            if (customResult !== true) {
                this.showFieldError(field, customResult || 'Validação falhou');
                return false;
            }
        }

        this.clearFieldError(field);
        return true;
    }

    /**
     * Valida um CPF usando o algoritmo de validação brasileiro
     *
     * @param {string} cpf - CPF a ser validado
     * @returns {boolean} True se válido, false caso contrário
     */
    validateCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) return false;
        if (/^(\d)\1+$/.test(cpf)) return false;

        // Primeiro dígito verificador
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let digit = 11 - (sum % 11);
        if (digit >= 10) digit = 0;
        if (digit !== parseInt(cpf.charAt(9))) return false;

        // Segundo dígito verificador
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        digit = 11 - (sum % 11);
        if (digit >= 10) digit = 0;
        if (digit !== parseInt(cpf.charAt(10))) return false;

        return true;
    }

    /**
     * Exibe erro em um campo
     *
     * @param {HTMLElement} field - Campo onde exibir o erro
     * @param {string} message - Mensagem de erro
     */
    showFieldError(field, message) {
        this.clearFieldError(field);

        field.style.borderColor = '#f44336';
        field.classList.add('error');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 5px; display: flex; align-items: center; gap: 5px; font-weight: 500;';

        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-circle';
        errorDiv.appendChild(icon);

        const text = document.createElement('span');
        text.textContent = message;
        errorDiv.appendChild(text);

        field.parentNode.appendChild(errorDiv);
        field.setAttribute('aria-invalid', 'true');
    }

    /**
     * Remove erro de um campo
     *
     * @param {HTMLElement} field - Campo onde remover o erro
     */
    clearFieldError(field) {
        field.style.borderColor = '';
        field.classList.remove('error');
        field.removeAttribute('aria-invalid');

        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Coleta dados do formulário
     *
     * @returns {Object} Objeto com os dados do formulário
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        return data;
    }

    /**
     * Obtém mensagem de erro de um campo
     *
     * @param {string} fieldName - Nome do campo
     * @returns {string} Mensagem de erro
     */
    getErrorMessage(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const errorDiv = field?.parentNode.querySelector('.field-error');
        return errorDiv?.textContent || 'Erro de validação';
    }

    /**
     * Define valores nos campos do formulário
     *
     * @param {Object} values - Objeto com nomes de campos e seus valores
     */
    setValues(values) {
        Object.keys(values).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.value = values[fieldName];
            }
        });
    }

    /**
     * Limpa o formulário
     */
    reset() {
        this.form.reset();
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            this.clearFieldError(field);
        });
    }
}

// Exportar para uso global
window.ValidationManager = ValidationManager;

console.log('✔️ Biblioteca de Validação carregada');
