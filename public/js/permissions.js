/**
 * Helper para verificar permissões do usuário no frontend
 */
class PermissionHelper {
    constructor() {
        this.user = window.safeParseJSON(localStorage.getItem('user'), null);
        this.permissions = window.safeParseJSON(localStorage.getItem('permissions'), []);
    }

    /**
     * Verifica se o usuário tem uma permissão
     */
    can(permission) {
        return this.permissions.includes(permission);
    }

    /**
     * Verifica se o usuário tem um dos roles especificados
     */
    hasRole(...roles) {
        return this.user && roles.includes(this.user.role);
    }

    /**
     * Verifica se o usuário é admin
     */
    isAdmin() {
        return this.hasRole('admin');
    }

    /**
     * Verifica se o usuário é supervisor
     */
    isSupervisor() {
        return this.hasRole('supervisor');
    }

    /**
     * Verifica se o usuário é operador
     */
    isOperador() {
        return this.hasRole('operador');
    }

    /**
     * Esconde elemento se o usuário não tiver permissão
     */
    hideIfNoPerm(selector, permission) {
        if (!this.can(permission)) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => el.style.display = 'none');
        }
    }

    /**
     * Desabilita elemento se o usuário não tiver permissão
     */
    disableIfNoPerm(selector, permission) {
        if (!this.can(permission)) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.disabled = true;
                el.style.opacity = '0.5';
                el.style.cursor = 'not-allowed';
            });
        }
    }

    /**
     * Mostra mensagem de permissão negada
     */
    showAccessDenied() {
        alert('Você não tem permissão para realizar esta ação.');
    }
}

// Criar instância global
window.permissions = new PermissionHelper();
