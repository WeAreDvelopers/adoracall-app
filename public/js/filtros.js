/**
 * ============================================
 * BIBLIOTECA DE FILTROS REUTILIZÁVEL
 * ============================================
 *
 * Classe FiltroManager para criar filtros dinâmicos e reutilizáveis
 * em diferentes páginas da aplicação
 */

class FiltroManager {
    /**
     * Construtor do FiltroManager
     *
     * @param {string} containerId - ID do elemento container onde os filtros serão renderizados
     * @param {Object} options - Opções de configuração
     * @param {Array} options.filters - Array de definição de filtros
     * @param {Function} options.onFilter - Callback executado ao aplicar filtros
     * @param {Function} options.onClear - Callback executado ao limpar filtros
     */
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);

        if (!this.container) {
            console.error(`Container com ID "${containerId}" não encontrado`);
            return;
        }

        this.filters = {};
        this.callbacks = {
            onFilter: options.onFilter || (() => {}),
            onClear: options.onClear || (() => {}),
        };

        this.renderFilters(options.filters || []);
    }

    /**
     * Renderiza o HTML dos filtros no container
     *
     * @param {Array} filtersConfig - Array de configuração dos filtros
     */
    renderFilters(filtersConfig) {
        let html = '<div class="filter-container" style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">';

        filtersConfig.forEach(filter => {
            html += this.renderFilter(filter);
        });

        html += `
            <button type="button" class="btn btn-primary" id="btnFiltrar" style="padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; background: #667eea; color: white; font-weight: 600; transition: all 0.3s;">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <button type="button" class="btn btn-secondary" id="btnLimpar" style="padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; background: #ddd; color: #333; font-weight: 600; transition: all 0.3s;">
                <i class="fas fa-undo"></i> Limpar
            </button>
        `;

        html += '</div>';

        this.container.innerHTML = html;
        this.attachEvents();
    }

    /**
     * Renderiza um filtro individual baseado em seu tipo
     *
     * @param {Object} filter - Configuração do filtro
     * @returns {string} HTML do filtro
     */
    renderFilter(filter) {
        let html = '<div class="filter-group" style="display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 200px;">';
        html += `<label for="${filter.id}" style="font-size: 12px; font-weight: 600; color: #666;">${filter.label}</label>`;

        switch (filter.type) {
            case 'text':
                html += `<input type="text" id="${filter.id}" placeholder="${filter.placeholder || ''}" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: border-color 0.3s;">`;
                break;

            case 'select':
                html += `<select id="${filter.id}" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: border-color 0.3s;">`;
                html += `<option value="">Todos</option>`;
                if (filter.options && Array.isArray(filter.options)) {
                    filter.options.forEach(opt => {
                        html += `<option value="${opt.value}">${opt.label}</option>`;
                    });
                }
                html += `</select>`;
                break;

            case 'date':
                html += `<input type="date" id="${filter.id}" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: border-color 0.3s;">`;
                break;

            case 'daterange':
                html += `
                    <div style="display: flex; gap: 10px;">
                        <input type="date" id="${filter.id}_start" placeholder="De" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; flex: 1; transition: border-color 0.3s;">
                        <input type="date" id="${filter.id}_end" placeholder="Até" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; flex: 1; transition: border-color 0.3s;">
                    </div>
                `;
                break;

            case 'number':
                html += `
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="${filter.id}_min" placeholder="Mín" step="any" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; flex: 1; transition: border-color 0.3s;">
                        <input type="number" id="${filter.id}_max" placeholder="Máx" step="any" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; flex: 1; transition: border-color 0.3s;">
                    </div>
                `;
                break;

            default:
                console.warn(`Tipo de filtro desconhecido: ${filter.type}`);
        }

        html += '</div>';
        return html;
    }

    /**
     * Anexa event listeners aos elementos de filtro
     */
    attachEvents() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const btnLimpar = document.getElementById('btnLimpar');

        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', () => this.applyFilters());
            btnFiltrar.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
            });
            btnFiltrar.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        }

        if (btnLimpar) {
            btnLimpar.addEventListener('click', () => this.clearFilters());
            btnLimpar.addEventListener('mouseenter', function() {
                this.style.background = '#ccc';
            });
            btnLimpar.addEventListener('mouseleave', function() {
                this.style.background = '#ddd';
            });
        }

        // Enter para filtrar em campos de texto e número
        this.container.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.applyFilters();
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
            });

            input.addEventListener('blur', function() {
                this.style.borderColor = '#ddd';
                this.style.boxShadow = 'none';
            });
        });

        // Efeito visual em selects
        this.container.querySelectorAll('select').forEach(select => {
            select.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
            });

            select.addEventListener('blur', function() {
                this.style.borderColor = '#ddd';
                this.style.boxShadow = 'none';
            });
        });
    }

    /**
     * Coleta os valores dos filtros ativos
     *
     * @returns {Object} Objeto com os valores dos filtros
     */
    getFilters() {
        const filters = {};

        this.container.querySelectorAll('input, select').forEach(el => {
            const value = el.value ? el.value.trim() : '';

            if (value) {
                // Para campos de range de data e número
                if (el.id.includes('_start') || el.id.includes('_min')) {
                    filters[el.id] = value;
                } else if (el.id.includes('_end') || el.id.includes('_max')) {
                    filters[el.id] = value;
                } else {
                    filters[el.id] = value;
                }
            }
        });

        return filters;
    }

    /**
     * Aplica os filtros chamando o callback onFilter
     */
    applyFilters() {
        const filters = this.getFilters();
        console.log('Filtros aplicados:', filters);
        this.callbacks.onFilter(filters);
    }

    /**
     * Limpa todos os filtros
     */
    clearFilters() {
        this.container.querySelectorAll('input, select').forEach(el => {
            el.value = '';
            el.style.borderColor = '#ddd';
            el.style.boxShadow = 'none';
        });

        console.log('Filtros limpos');
        this.callbacks.onClear();
    }

    /**
     * Define valores nos filtros programaticamente
     *
     * @param {Object} values - Objeto com IDs de filtros e seus valores
     */
    setFilters(values) {
        Object.keys(values).forEach(id => {
            const el = this.container.querySelector(`#${id}`);
            if (el) {
                el.value = values[id];
            }
        });
    }

    /**
     * Retorna os filtros como string de query parameters
     *
     * @returns {string} Query string (ex: "busca=teste&status=active")
     */
    getQueryString() {
        const filters = this.getFilters();
        const params = new URLSearchParams(filters);
        return params.toString();
    }
}

// Exportar para uso global
window.FiltroManager = FiltroManager;

console.log('📊 Biblioteca de Filtros carregada');
