/**
 * Sidebar Collapsible Navigation
 *
 * Gerencia seções colapsáveis do sidebar com persistência de estado
 * no localStorage e auto-expansão de seções ativas.
 */

console.log('[Sidebar] Script loaded successfully');

/**
 * Alterna expand/collapse de uma seção do sidebar
 * @param {HTMLElement} headerElement - Elemento header clicado
 */
function toggleSidebarSection(headerElement) {
    const section = headerElement.closest('.sidebar-section');
    if (!section) {
        console.warn('[Sidebar] Could not find parent section');
        return;
    }

    const sectionName = section.getAttribute('data-section');
    const content = section.querySelector('.sidebar-section-content');
    const chevron = headerElement.querySelector('.sidebar-section-chevron');
    const isExpanded = headerElement.getAttribute('data-expanded') === 'true';

    // Alterna estado
    const newState = !isExpanded;
    headerElement.setAttribute('data-expanded', newState);

    // Anima chevron (rotação)
    if (chevron) {
        chevron.style.transform = newState ? 'rotate(90deg)' : 'rotate(0deg)';
    }

    // Anima content (max-height + opacity)
    if (content) {
        if (newState) {
            // Expandindo
            const scrollHeight = content.scrollHeight;
            content.style.maxHeight = scrollHeight + 'px';
            content.style.opacity = '1';
        } else {
            // Colapsando
            content.style.maxHeight = '0';
            content.style.opacity = '0';
        }
    }

    // Salva estado no localStorage
    saveSidebarState(sectionName, newState);
}

/**
 * Salva estado de uma seção no localStorage
 * @param {string} sectionName - Nome da seção
 * @param {boolean} isExpanded - Estado expandido/colapsado
 */
function saveSidebarState(sectionName, isExpanded) {
    try {
        const state = safeParseJSON(localStorage.getItem('sidebar_state'), {});
        state[sectionName] = isExpanded;
        localStorage.setItem('sidebar_state', JSON.stringify(state));
    } catch (error) {
        console.warn('Erro ao salvar sidebar state:', error);
    }
}

/**
 * Carrega estado salvo do localStorage e aplica às seções
 */
function loadSidebarState() {
    try {
        const state = safeParseJSON(localStorage.getItem('sidebar_state'), {});

        document.querySelectorAll('.sidebar-section').forEach(section => {
            const sectionName = section.getAttribute('data-section');
            const header = section.querySelector('.sidebar-section-header');
            const content = section.querySelector('.sidebar-section-content');
            const chevron = header ? header.querySelector('.sidebar-section-chevron') : null;

            if (!header || !content) return;

            // Usa estado salvo ou detecta default baseado em breakpoint
            let isExpanded;
            if (state.hasOwnProperty(sectionName)) {
                isExpanded = state[sectionName];
            } else {
                // Default: expandido no desktop, colapsado no mobile
                isExpanded = window.innerWidth > 768;
            }

            // Aplica estado
            applyStateToSection(header, content, chevron, isExpanded);
        });
    } catch (error) {
        console.warn('Erro ao carregar sidebar state:', error);
        // Fallback: todas expandidas
        expandAllSections();
    }
}

/**
 * Aplica estado expandido/colapsado a uma seção
 * @param {HTMLElement} header - Header da seção
 * @param {HTMLElement} content - Content da seção
 * @param {HTMLElement} chevron - Ícone chevron
 * @param {boolean} isExpanded - Estado desejado
 */
function applyStateToSection(header, content, chevron, isExpanded) {
    header.setAttribute('data-expanded', isExpanded);

    if (chevron) {
        chevron.style.transform = isExpanded ? 'rotate(90deg)' : 'rotate(0deg)';
    }

    if (isExpanded) {
        content.style.maxHeight = content.scrollHeight + 'px';
        content.style.opacity = '1';
    } else {
        content.style.maxHeight = '0';
        content.style.opacity = '0';
    }
}

/**
 * Auto-expande a seção que contém o item ativo
 */
function autoExpandActiveSection() {
    const activeItem = document.querySelector('.sidebar-item.active');
    if (!activeItem) return;

    const section = activeItem.closest('.sidebar-section');
    if (!section) return;

    const header = section.querySelector('.sidebar-section-header');
    const content = section.querySelector('.sidebar-section-content');
    const chevron = header ? header.querySelector('.sidebar-section-chevron') : null;

    const isExpanded = header.getAttribute('data-expanded') === 'true';

    // Se não está expandido, expande
    if (!isExpanded) {
        applyStateToSection(header, content, chevron, true);
        header.setAttribute('data-expanded', 'true');
        saveSidebarState(section.getAttribute('data-section'), true);
    }
}

/**
 * Expande todas as seções (fallback)
 */
function expandAllSections() {
    document.querySelectorAll('.sidebar-section').forEach(section => {
        const header = section.querySelector('.sidebar-section-header');
        const content = section.querySelector('.sidebar-section-content');
        const chevron = header ? header.querySelector('.sidebar-section-chevron') : null;

        if (header && content) {
            applyStateToSection(header, content, chevron, true);
        }
    });
}

/**
 * Inicializa o sidebar na página
 */
function initSidebar() {
    console.log('[Sidebar] Initializing...');

    // Carrega estado salvo
    loadSidebarState();

    // Auto-expande seção com item ativo
    autoExpandActiveSection();

    // Listener para ajustar heights em caso de resize (importante para animações)
    window.addEventListener('resize', () => {
        document.querySelectorAll('.sidebar-section-content').forEach(content => {
            const header = content.parentElement.querySelector('.sidebar-section-header');
            if (header && header.getAttribute('data-expanded') === 'true') {
                // Recalcula altura para responsividade
                setTimeout(() => {
                    content.style.maxHeight = content.scrollHeight + 'px';
                }, 0);
            }
        });
    });
}

/**
 * Inicia sidebar quando o DOM está pronto
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
