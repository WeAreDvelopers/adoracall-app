// Configurações
let currentPage = 1;
let currentMailingId = null;
let currentFilters = {};
let currentFileData = null;
let convertedCsvFile = null; // Guarda CSV convertido de XLSX

// Status mapping para tradução e cores
const STATUS_MAP = {
    'importado': { label: 'Importado', color: 'status-importado' },
    'enviado_a_discagem': { label: 'Enviado a Discagem', color: 'status-enviado_a_discagem' },
    'em_ligacao': { label: 'Em Ligação', color: 'status-em_ligacao' },
    'finalizado': { label: 'Finalizado', color: 'status-finalizado' },
    'retentar': { label: 'Retentar', color: 'status-retentar' }
};

// Colunas obrigatórias no CSV
const REQUIRED_COLUMNS = ['nome', 'telefone'];
const OPTIONAL_COLUMNS = ['cpf', 'data_nascimento', 'valor_debito', 'vencimento', 'campanha'];
const ALL_COLUMNS = [...REQUIRED_COLUMNS, ...OPTIONAL_COLUMNS];

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    carregarMailings();
    carregarHistoricoImportacoes();

    document.getElementById('importForm').addEventListener('submit', handleImportSubmit);
    document.getElementById('arquivo').addEventListener('change', handleFileSelect);

    document.getElementById('statusFilter').addEventListener('change', () => {
        currentPage = 1;
        aplicarFiltros();
    });
    document.getElementById('searchFilter').addEventListener('keyup', debounce(() => {
        currentPage = 1;
        aplicarFiltros();
    }, 500));
});

/**
 * Verifica se o arquivo é Excel
 */
function isExcelFile(file) {
    const excelExts = ['.xlsx', '.xls'];
    const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
    return excelExts.includes(ext);
}

/**
 * Handle quando arquivo é selecionado
 */
async function handleFileSelect(e) {
    const file = e.target.files[0];
    convertedCsvFile = null;

    if (!file) {
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    const isExcel = isExcelFile(file);
    const isCsv = file.type === 'text/csv' || file.name.endsWith('.csv');

    if (!isCsv && !isExcel) {
        mostrarErro('Por favor, selecione um arquivo CSV ou Excel (.xlsx) válido');
        document.getElementById('arquivo').value = '';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        mostrarErro('Arquivo muito grande. Máximo 10MB');
        document.getElementById('arquivo').value = '';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    try {
        let csvText;

        if (isExcel) {
            // Converter XLSX para CSV usando a lib XLSX
            if (typeof XLSX === 'undefined') {
                mostrarErro('Biblioteca XLSX não carregada. Recarregue a página.');
                return;
            }
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            csvText = XLSX.utils.sheet_to_csv(firstSheet);

            // Criar File CSV convertido para enviar ao backend
            const blob = new Blob([csvText], { type: 'text/csv' });
            const nomeBase = file.name.replace(/\.(xlsx|xls)$/i, '');
            convertedCsvFile = new File([blob], nomeBase + '.csv', { type: 'text/csv' });
        } else {
            csvText = await file.text();
        }

        currentFileData = parseCSV(csvText);
        validarEExibirPreview(currentFileData);
    } catch (error) {
        mostrarErro('Erro ao ler arquivo: ' + error.message);
        document.getElementById('arquivo').value = '';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
    }
}

/**
 * Parse CSV simples
 */
function parseCSV(csv) {
    const lines = csv.trim().split('\n');
    if (lines.length === 0) return null;

    const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
    const data = [];

    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim());
        if (values.join('').length === 0) continue; // Ignorar linhas vazias

        const row = {};
        headers.forEach((header, idx) => {
            row[header] = values[idx] || '';
        });
        data.push(row);
    }

    return { headers, data };
}

/**
 * Valida e exibe preview do CSV
 */
function validarEExibirPreview(fileData) {
    if (!fileData) return;

    const previewSection = document.getElementById('previewSection');
    const previewBody = document.getElementById('previewBody');
    const previewErrors = document.getElementById('previewErrors');
    const submitBtn = document.getElementById('submitBtn');

    let htmlHeaders = '';
    let errors = [];
    let isValid = true;

    // Validar colunas obrigatórias
    const headersLower = fileData.headers.map(h => h.toLowerCase());
    const missingRequired = REQUIRED_COLUMNS.filter(col => !headersLower.includes(col));

    if (missingRequired.length > 0) {
        errors.push(`Colunas obrigatórias faltando: ${missingRequired.join(', ')}`);
        isValid = false;
    }

    // Construir header do preview
    fileData.headers.forEach(header => {
        const isMissing = missingRequired.includes(header.toLowerCase());
        const style = isMissing ? 'background: #ffebee; color: #d32f2f;' : '';
        htmlHeaders += `<th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd; ${style}">${header}</th>`;
    });

    // Limpar e popular preview (primeiras 5 linhas)
    previewBody.innerHTML = '';
    fileData.data.slice(0, 5).forEach((row, idx) => {
        const tr = document.createElement('tr');
        const tdNum = document.createElement('td');
        tdNum.textContent = idx + 1;
        tdNum.style.cssText = 'padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; background: #f9f9f9;';
        tr.appendChild(tdNum);

        fileData.headers.forEach(header => {
            const td = document.createElement('td');
            td.textContent = row[header] || '-';
            td.style.cssText = 'padding: 10px; border-bottom: 1px solid #eee; font-size: 12px;';
            tr.appendChild(td);
        });

        previewBody.appendChild(tr);
    });

    // Mostrar total de linhas
    const totalLinhas = fileData.data.length;
    errors.unshift(`<strong>Total de linhas:</strong> ${totalLinhas}`);

    // Validar dados (amostragem)
    let telefonesInvalidos = 0;
    let cpfsInvalidos = 0;

    fileData.data.slice(0, Math.min(20, fileData.data.length)).forEach(row => {
        if (row.telefone && !validarTelefone(row.telefone)) {
            telefonesInvalidos++;
        }
        if (row.cpf && !validarCPF(row.cpf)) {
            cpfsInvalidos++;
        }
    });

    if (telefonesInvalidos > 0) {
        errors.push(`⚠️ Possíveis telefones inválidos encontrados (amostragem)`);
    }
    if (cpfsInvalidos > 0) {
        errors.push(`⚠️ Possíveis CPFs inválidos encontrados (amostragem)`);
    }

    // Exibir preview
    document.getElementById('previewHeaders').innerHTML = htmlHeaders;
    previewErrors.innerHTML = errors.map(e => `<div>${e}</div>`).join('');
    previewSection.style.display = 'block';

    submitBtn.disabled = !isValid;
}

/**
 * Valida telefone brasileiro
 */
function validarTelefone(telefone) {
    const numeros = telefone.replace(/[^0-9]/g, '');
    return numeros.length === 11;
}

/**
 * Valida CPF simples
 */
function validarCPF(cpf) {
    const numeros = cpf.replace(/[^0-9]/g, '');
    return numeros.length === 11;
}

/**
 * Carrega histórico de importações
 */
function carregarHistoricoImportacoes() {
    fetch(`${API_BASE_URL}/filas_campanha`)
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                exibirHistorico(data.data);
            }
        })
        .catch(error => console.error('Erro ao carregar histórico:', error));
}

/**
 * Exibe histórico de importações
 */
function exibirHistorico(mailings) {
    const container = document.getElementById('historyContainer');
    const tbody = document.getElementById('historyBody');

    if (mailings.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';

    tbody.innerHTML = mailings.slice(0, 10).map(mailing => {
        const totalContatos = mailing.total_contatos || 0;
        const taxaSucesso = totalContatos > 0 ? Math.round((mailing.contatos_sucesso || 0) / totalContatos * 100) : 0;
        const dataFormatada = new Date(mailing.created_at).toLocaleString('pt-BR');

        return `
            <tr>
                <td>${dataFormatada}</td>
                <td>${mailing.nome}</td>
                <td>${totalContatos}</td>
                <td style="color: #4caf50; font-weight: bold;">${mailing.contatos_sucesso || 0}</td>
                <td style="color: #f44336; font-weight: bold;">${(totalContatos - (mailing.contatos_sucesso || 0)) || 0}</td>
                <td>
                    <span style="background: ${taxaSucesso >= 80 ? '#e8f5e9' : taxaSucesso >= 50 ? '#fff3e0' : '#ffebee'}; color: ${taxaSucesso >= 80 ? '#4caf50' : taxaSucesso >= 50 ? '#f57c00' : '#f44336'}; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                        ${taxaSucesso}%
                    </span>
                </td>
                <td>
                    <button class="btn-small btn-view" onclick="selecionarMailingHistorico(${mailing.id})">
                        <i class="fas fa-folder-open"></i> Ver
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Seleciona mailing do histórico
 */
function selecionarMailingHistorico(mailingId) {
    document.getElementById('mailing_id').value = mailingId;
    currentMailingId = mailingId;
    carregarContatosEStats();

    // Scroll para seção de estatísticas
    setTimeout(() => {
        document.getElementById('statsContainer').scrollIntoView({ behavior: 'smooth' });
    }, 300);
}

/**
 * Carrega lista de mailings disponíveis
 */
function carregarMailings() {
    console.log('🔄 Iniciando carregarMailings com API_BASE_URL:', API_BASE_URL);

    fetch(`${API_BASE_URL}/filas_campanha`)
        .then(response => {
            console.log('📊 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📨 Dados recebidos:', data);

            const select = document.getElementById('mailing_id');
            console.log('✅ Select element encontrado:', select);

            // Limpar todas as opções exceto a primeira (padrão)
            while (select.options.length > 1) {
                select.remove(1);
            }

            // Verificar se há uma campanha criada recentemente no localStorage
            const campanhaAcabadoCriar = localStorage.getItem('campanhaAcabadoCriar');
            console.log('💾 Campanha do localStorage:', campanhaAcabadoCriar);

            if (data.data && data.data.length > 0) {
                console.log('📝 Adicionando', data.data.length, 'campanhas ao select');

                data.data.forEach(mailing => {
                    const option = document.createElement('option');
                    option.value = mailing.id;
                    option.textContent = `${mailing.nome} (${mailing.total_contatos} contatos)`;
                    select.appendChild(option);
                    console.log('  ➕ Adicionado:', mailing.nome);
                });

                // Se houver campanha criada recentemente, selecionar automaticamente
                if (campanhaAcabadoCriar) {
                    select.value = campanhaAcabadoCriar;
                    currentMailingId = campanhaAcabadoCriar;

                    // Limpar do localStorage
                    localStorage.removeItem('campanhaAcabadoCriar');

                    // Carregar contatos e stats da campanha
                    carregarContatosEStats();

                    // Mostrar notificação
                    mostrarSucesso(`Campanha selecionada! Agora importe os contatos.`);
                }
            } else {
                console.warn('⚠️ Nenhuma campanha encontrada');
            }

            // Adicionar listener para mudança de mailing
            select.addEventListener('change', () => {
                currentMailingId = select.value;
                if (currentMailingId) {
                    carregarContatosEStats();
                } else {
                    limparListagem();
                }
            });
        })
        .catch(error => {
            console.error('🚨 Erro ao carregar campanhas:', error);
            console.error('   Stack:', error.stack);
            mostrarErro('Erro ao carregar campanhas');
        });
}

/**
 * Handle do submit do formulário de importação
 */
function handleImportSubmit(e) {
    e.preventDefault();

    const mailingId = document.getElementById('mailing_id').value;
    const arquivoOriginal = document.getElementById('arquivo').files[0];

    if (!mailingId || !arquivoOriginal) {
        mostrarErro('Selecione uma campanha e um arquivo');
        return;
    }

    // Se era XLSX, envia o CSV convertido; senão envia o original
    const arquivo = convertedCsvFile || arquivoOriginal;

    const formData = new FormData();
    formData.append('arquivo', arquivo);

    document.getElementById('importStatus').style.display = 'block';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;

    fetch(`${API_BASE_URL}/filas_campanha/${mailingId}/importar`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('importStatus').style.display = 'none';

        if (data.error) {
            mostrarErroImportacao(data.error, data.errors);
        } else {
            mostrarSucessoImportacao(data);
            resetForm();
            carregarContatosEStats();
        }
    })
    .catch(error => {
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('importStatus').style.display = 'none';
        console.error('Erro na importação:', error);
        mostrarErro('Erro ao fazer upload do arquivo');
    });
}

/**
 * Carrega contatos e estatísticas do mailing selecionado
 */
function carregarContatosEStats() {
    if (!currentMailingId) return;

    // Carregar estatísticas
    fetch(`${API_BASE_URL}/filas_campanha/${currentMailingId}/contatos/stats`)
        .then(response => response.json())
        .then(data => {
            exibirEstatisticas(data);
        })
        .catch(error => console.error('Erro ao carregar estatísticas:', error));

    // Carregar contatos
    carregarContatos(1);
}

/**
 * Exibe estatísticas dos contatos
 */
function exibirEstatisticas(data) {
    const container = document.getElementById('statsContainer');
    const grid = document.getElementById('statsGrid');

    if (!data.resumo) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';

    const stats = [
        { key: 'importado', label: 'Importado', class: 'importado' },
        { key: 'enviado_a_discagem', label: 'Enviado a Discagem', class: 'enviado' },
        { key: 'em_ligacao', label: 'Em Ligação', class: 'ligacao' },
        { key: 'finalizado', label: 'Finalizado', class: 'finalizado' },
        { key: 'retentar', label: 'Retentar', class: 'retentar' }
    ];

    grid.innerHTML = stats.map(stat => `
        <div class="stat-card ${stat.class}">
            <div class="stat-label">${stat.label}</div>
            <div class="stat-value">${data.resumo[stat.key] || 0}</div>
        </div>
    `).join('');
}

/**
 * Carrega contatos com paginação e filtros
 */
function carregarContatos(page = 1) {
    if (!currentMailingId) return;

    currentPage = page;

    let url = `${API_BASE_URL}/filas_campanha/${currentMailingId}/contatos?page=${page}&per_page=10`;

    if (currentFilters.status) {
        url += `&status=${currentFilters.status}`;
    }

    if (currentFilters.buscar) {
        url += `&buscar=${encodeURIComponent(currentFilters.buscar)}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            exibirContatos(data);
        })
        .catch(error => {
            console.error('Erro ao carregar contatos:', error);
            mostrarErro('Erro ao carregar contatos');
        });
}

/**
 * Exibe contatos na tabela
 */
function exibirContatos(data) {
    const tbody = document.getElementById('contatosTableBody');
    const container = document.getElementById('contatosContainer');

    if (!data.data || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fas fa-inbox"></i><p>Nenhum contato encontrado</p></td></tr>';
        document.getElementById('paginationContainer').style.display = 'none';
        return;
    }

    container.style.display = 'block';

    tbody.innerHTML = data.data.map(contato => {
        const statusInfo = STATUS_MAP[contato.status] || { label: contato.status, color: '' };
        const telefoneFormatado = formatarTelefone(contato.telefone);
        const cpfFormatado = contato.cpf_primeiros_digitos ? `***-***-${contato.cpf_primeiros_digitos}` : 'N/A';

        return `
            <tr>
                <td>${contato.nome}</td>
                <td>${telefoneFormatado}</td>
                <td>${cpfFormatado}</td>
                <td>R$ ${parseFloat(contato.valor_debito).toFixed(2).replace('.', ',')}</td>
                <td>
                    <span class="import-status-badge ${statusInfo.color}">
                        ${statusInfo.label}
                    </span>
                </td>
                <td>${contato.tentativas || 0}</td>
                <td class="actions-column">
                    <button class="btn-small btn-view" onclick="visualizarContato(${contato.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Mostrar paginação
    if (data.last_page > 1) {
        exibirPaginacao(data);
        document.getElementById('paginationContainer').style.display = 'block';
    } else {
        document.getElementById('paginationContainer').style.display = 'none';
    }
}

/**
 * Exibe paginação
 */
function exibirPaginacao(data) {
    const container = document.getElementById('pagination');
    let html = '';

    // Anterior
    if (data.current_page > 1) {
        html += `<a onclick="carregarContatos(${data.current_page - 1})"><i class="fas fa-chevron-left"></i></a>`;
    }

    // Páginas
    const startPage = Math.max(1, data.current_page - 2);
    const endPage = Math.min(data.last_page, data.current_page + 2);

    if (startPage > 1) {
        html += `<a onclick="carregarContatos(1)">1</a>`;
        if (startPage > 2) html += '<span>...</span>';
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === data.current_page) {
            html += `<span class="active">${i}</span>`;
        } else {
            html += `<a onclick="carregarContatos(${i})">${i}</a>`;
        }
    }

    if (endPage < data.last_page) {
        if (endPage < data.last_page - 1) html += '<span>...</span>';
        html += `<a onclick="carregarContatos(${data.last_page})">${data.last_page}</a>`;
    }

    // Próxima
    if (data.current_page < data.last_page) {
        html += `<a onclick="carregarContatos(${data.current_page + 1})"><i class="fas fa-chevron-right"></i></a>`;
    }

    container.innerHTML = html;
}

/**
 * Aplica filtros de busca e status
 */
function aplicarFiltros() {
    currentFilters.status = document.getElementById('statusFilter').value;
    currentFilters.buscar = document.getElementById('searchFilter').value;
    carregarContatos(1);
}

/**
 * Limpa filtros
 */
function limparFiltros() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchFilter').value = '';
    currentFilters = {};
    carregarContatos(1);
}

/**
 * Visualiza detalhes do contato
 */
function visualizarContato(contatoId) {
    const tbody = document.getElementById('contatosTableBody');
    const contato = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        return tr.querySelector('[onclick*="visualizarContato"]')?.getAttribute('onclick').includes(contatoId);
    });

    if (!contato) return;

    const cells = contato.querySelectorAll('td');
    const statusBadge = cells[4].querySelector('.import-status-badge');

    const body = document.getElementById('modalBody');
    const statusText = statusBadge.textContent.trim();

    body.innerHTML = `
        <div class="info-row">
            <span class="info-label">Nome</span>
            <span class="info-value">${cells[0].textContent}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Telefone</span>
            <span class="info-value">${cells[1].textContent}</span>
        </div>
        <div class="info-row">
            <span class="info-label">CPF</span>
            <span class="info-value">${cells[2].textContent}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Valor Débito</span>
            <span class="info-value">${cells[3].textContent}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value">
                <span class="import-status-badge ${statusBadge.className.split(' ').pop()}">
                    ${statusText}
                </span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Tentativas</span>
            <span class="info-value">${cells[5].textContent}</span>
        </div>
    `;

    document.getElementById('contatoModal').classList.add('show');
}

/**
 * Fecha modal de contato
 */
function fecharModal() {
    document.getElementById('contatoModal').classList.remove('show');
}

/**
 * Mostra sucesso na importação
 */
function mostrarSucessoImportacao(data) {
    const resultDiv = document.getElementById('importResult');
    resultDiv.innerHTML = `
        <div class="alert alert-success" style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 4px; border-left: 4px solid #2e7d32;">
            <i class="fas fa-check-circle"></i>
            <strong>Importação realizada com sucesso!</strong>
            <p style="margin: 10px 0 0 0; font-size: 13px;">
                ${data.message || 'Contatos foram importados e adicionados à fila de processamento'}
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #1976d2;">
                <i class="fas fa-info-circle"></i> Redirecionando para status de importação em 3 segundos...
            </p>
        </div>
    `;
    resultDiv.style.display = 'block';

    // Redirecionar para página de status após 3 segundos
    setTimeout(() => {
        window.location.href = 'status-importacoes.html';
    }, 3000);
}

/**
 * Mostra erro na importação
 */
function mostrarErroImportacao(error, details) {
    const resultDiv = document.getElementById('importResult');
    let html = `
        <div class="alert alert-danger" style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; border-left: 4px solid #c62828;">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Erro na importação:</strong>
            <p style="margin: 10px 0 0 0; font-size: 13px;">${error}</p>
    `;

    if (details && typeof details === 'object') {
        html += '<ul style="margin: 10px 0 0 0; padding-left: 20px;">';
        Object.values(details).forEach(err => {
            if (typeof err === 'string') {
                html += `<li>${err}</li>`;
            } else if (Array.isArray(err)) {
                err.forEach(e => html += `<li>${e}</li>`);
            }
        });
        html += '</ul>';
    }

    html += '</div>';
    resultDiv.innerHTML = html;
    resultDiv.style.display = 'block';
}

/**
 * Mostra erro genérico
 */
function mostrarErro(mensagem) {
    alert(mensagem);
}

/**
 * Formata telefone
 */
function formatarTelefone(telefone) {
    const numero = telefone.replace(/[^0-9]/g, '');

    if (numero.length === 11) {
        return `(${numero.substring(0, 2)}) ${numero.substring(2, 7)}-${numero.substring(7)}`;
    }

    return telefone;
}

/**
 * Reset form
 */
function resetForm() {
    document.getElementById('importForm').reset();
    currentMailingId = null;
}

/**
 * Limpa listagem de contatos
 */
function limparListagem() {
    document.getElementById('contatosContainer').style.display = 'none';
    document.getElementById('statsContainer').style.display = 'none';
    document.getElementById('contatosTableBody').innerHTML = '<tr><td colspan="7" class="empty-state"><i class="fas fa-inbox"></i><p>Selecione uma campanha para visualizar contatos</p></td></tr>';
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(e) {
    const modal = document.getElementById('contatoModal');
    if (e.target === modal) {
        fecharModal();
    }
});
