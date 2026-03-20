/**
 * campanha-detalhe.js
 * Hub da campanha com 4 tabs: Resumo, Contatos, Chamadas, Importar
 * Lazy-loading: cada tab só busca dados quando ativada pela primeira vez
 */

let campanhaData = null;
let contatosLoaded = false;
let chamadasLoaded = false;
let importarLoaded = false;

// Contatos state
let contatosList = [];
let contatosFiltrados = [];
let contatosPagina = 1;
const contatosPerPage = 20;

// Chamadas state
let chamadasPage = 1;

// Import state
let convertedCsvFile = null;

// ==================== INIT ====================

document.addEventListener('DOMContentLoaded', function () {
    carregarCampanha();
});

// ==================== TABS ====================

function switchTab(tab) {
    // Update button styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
        btn.classList.add('text-slate-500', 'hover:text-slate-700');
    });
    const tabMap = { resumo: 'tabResumo', contatos: 'tabContatos', chamadas: 'tabChamadas', importar: 'tabImportar' };
    const activeBtn = document.getElementById(tabMap[tab]);
    activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
    activeBtn.classList.remove('text-slate-500', 'hover:text-slate-700');

    // Toggle content
    ['Resumo', 'Contatos', 'Chamadas', 'Importar'].forEach(t => {
        const el = document.getElementById('tabContent' + t);
        if (el) el.classList.toggle('hidden', t.toLowerCase() !== tab);
    });

    // Lazy load
    if (tab === 'contatos' && !contatosLoaded) { carregarContatos(); contatosLoaded = true; }
    if (tab === 'chamadas' && !chamadasLoaded) { carregarChamadas(); chamadasLoaded = true; }
    if (tab === 'importar' && !importarLoaded) { initImportForm(); importarLoaded = true; }
}

// ==================== TAB RESUMO ====================

async function carregarCampanha() {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}`);
        if (!response.ok) throw new Error('Campanha nao encontrada');
        campanhaData = await response.json();
        renderizarResumo();
    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('campanhaTitle').textContent = 'Erro ao carregar campanha';
    }
}

function renderizarResumo() {
    const d = campanhaData.data;
    const stats = d.stats || {};
    const progresso = stats.progresso || 0;
    const taxaSucesso = stats.taxa_sucesso || 0;

    // Header
    document.getElementById('breadcrumbName').textContent = d.nome || 'Campanha';
    document.getElementById('campanhaTitle').textContent = d.nome || 'Campanha';
    document.getElementById('campanhaDesc').textContent = d.descricao || '';
    document.title = `${d.nome} - URA Dvelopers`;

    // Breadcrumb no header global
    const bc = document.getElementById('breadcrumbContainer');
    if (bc) {
        bc.innerHTML = `
            <a href="/" class="hover:text-slate-600">Home</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="/campanhas" class="hover:text-slate-600">Campanhas</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-700 font-medium">${escapeHtml(d.nome)}</span>
        `;
    }

    // Status com cor
    const statusColors = {
        rascunho: 'text-slate-600', pronto: 'text-blue-600', ativo: 'text-emerald-600',
        pausado: 'text-amber-600', concluido: 'text-violet-600', cancelado: 'text-red-600'
    };
    document.getElementById('infoStatus').textContent = capitalize(d.status);
    document.getElementById('infoStatus').className = `text-lg font-bold mt-1 ${statusColors[d.status] || 'text-slate-900'}`;

    document.getElementById('infoPrioridade').textContent = capitalize(d.prioridade || 'normal');
    document.getElementById('infoTaxa').textContent = taxaSucesso.toFixed(1) + '%';
    document.getElementById('infoProgresso').textContent = Math.round(progresso) + '%';
    document.getElementById('progressBar').style.width = Math.min(progresso, 100) + '%';

    // Stats
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPendentes').textContent = stats.pendentes || 0;
    document.getElementById('statProcessando').textContent = stats.processando || 0;
    document.getElementById('statCompletados').textContent = stats.completados || 0;
    document.getElementById('statFalhados').textContent = stats.falhados || 0;

    // Detalhes
    document.getElementById('detailsGrid').innerHTML = `
        <div>
            <p class="text-slate-400 text-xs uppercase font-medium mb-1">Tipo Publico</p>
            <p class="font-medium text-slate-900">${capitalize(d.tipo_publico || 'N/A')}</p>
        </div>
        <div>
            <p class="text-slate-400 text-xs uppercase font-medium mb-1">Tentativas Max</p>
            <p class="font-medium text-slate-900">${d.max_tentativas || 'N/A'}</p>
        </div>
        <div>
            <p class="text-slate-400 text-xs uppercase font-medium mb-1">Velocidade</p>
            <p class="font-medium text-slate-900">${d.velocidade_contatos_hora || 'N/A'} contatos/hora</p>
        </div>
        <div>
            <p class="text-slate-400 text-xs uppercase font-medium mb-1">Intervalo Retry</p>
            <p class="font-medium text-slate-900">${d.intervalo_retry || 'N/A'} min</p>
        </div>
    `;

    // Action buttons
    renderActionButtons(d.status);
}

function renderActionButtons(status) {
    const container = document.getElementById('headerActions');
    let html = '';

    if (status === 'pronto' || status === 'pausado') {
        const label = status === 'pausado' ? 'Retomar' : 'Ativar';
        const acao = status === 'pausado' ? 'retomar' : 'ativar';
        html += `<button onclick="executarAcao('${acao}')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-play"></i> ${label}
        </button>`;
    }
    if (status === 'ativo') {
        html += `<button onclick="executarAcao('pausar')" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-pause"></i> Pausar
        </button>`;
    }
    if (status !== 'cancelado' && status !== 'concluido') {
        html += `<button onclick="executarAcao('cancelar')" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 hover:bg-red-50 text-red-600 text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-times"></i> Cancelar
        </button>`;
    }

    container.innerHTML = html;
}

async function executarAcao(acao) {
    if (acao === 'cancelar' && !confirm('Tem certeza que deseja cancelar esta campanha?')) return;

    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/${acao}`, { method: 'POST' });
        if (response.ok) {
            showToast(`Campanha ${acao}da com sucesso!`, 'success');
            setTimeout(() => carregarCampanha(), 1000);
        } else {
            const data = await response.json();
            showToast(data.message || data.error || 'Erro ao executar acao', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro: ' + error.message, 'error');
    }
}

// ==================== TAB CONTATOS ====================

async function carregarContatos() {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/contatos`);
        if (!response.ok) throw new Error('Erro ao carregar contatos');
        const data = await response.json();
        contatosList = Array.isArray(data) ? data : (data.data || []);
        contatosFiltrados = [...contatosList];
        contatosPagina = 1;
        renderizarContatos();
    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('contatosBody').innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center text-red-400"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar contatos</td></tr>`;
    }
}

function renderizarContatos() {
    const inicio = (contatosPagina - 1) * contatosPerPage;
    const fim = inicio + contatosPerPage;
    const pagina = contatosFiltrados.slice(inicio, fim);
    const tbody = document.getElementById('contatosBody');

    if (pagina.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-inbox text-xl"></i><p class="mt-2 text-sm">Nenhum contato encontrado</p></td></tr>`;
        document.getElementById('contatosPagInfo').textContent = '0 contatos';
        return;
    }

    const statusBadge = (status) => {
        const map = {
            pendente: 'bg-amber-100 text-amber-700',
            processando: 'bg-blue-100 text-blue-700',
            completado: 'bg-emerald-100 text-emerald-700',
            falha: 'bg-red-100 text-red-700'
        };
        return map[status] || 'bg-slate-100 text-slate-700';
    };

    tbody.innerHTML = pagina.map(c => `
        <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-3 text-sm text-slate-900 font-medium">${escapeHtml(c.nome || 'N/A')}</td>
            <td class="px-6 py-3 text-sm text-slate-600 font-mono">${c.telefone || 'N/A'}</td>
            <td class="px-6 py-3 text-sm text-slate-600">${maskCPF(c.cpf)}</td>
            <td class="px-6 py-3 text-sm text-slate-600">R$ ${parseFloat(c.valor_debito || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
            <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge(c.status)}">${capitalize(c.status || 'pendente')}</span></td>
            <td class="px-6 py-3 text-sm text-slate-600">${c.tentativas || 0}</td>
            <td class="px-6 py-3 text-sm text-slate-500">${c.ultima_ligacao ? new Date(c.ultima_ligacao).toLocaleString('pt-BR') : '-'}</td>
            <td class="px-6 py-3"><a href="/contatos/${c.id}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">Detalhes</a></td>
        </tr>
    `).join('');

    const total = contatosFiltrados.length;
    document.getElementById('contatosPagInfo').textContent = `${inicio + 1}-${Math.min(fim, total)} de ${total} contatos`;
    document.getElementById('contatosPrev').disabled = contatosPagina <= 1;
    document.getElementById('contatosNext').disabled = fim >= total;
}

function filtrarContatos() {
    const search = (document.getElementById('contatoSearch').value || '').toLowerCase();
    const status = document.getElementById('contatoStatus').value;
    contatosFiltrados = contatosList.filter(c => {
        const matchStatus = !status || c.status === status;
        const matchSearch = !search ||
            (c.nome && c.nome.toLowerCase().includes(search)) ||
            (c.telefone && c.telefone.includes(search)) ||
            (c.cpf && c.cpf.includes(search));
        return matchStatus && matchSearch;
    });
    contatosPagina = 1;
    renderizarContatos();
}

function limparFiltrosContatos() {
    document.getElementById('contatoSearch').value = '';
    document.getElementById('contatoStatus').value = '';
    contatosFiltrados = [...contatosList];
    contatosPagina = 1;
    renderizarContatos();
}

function contatosPaginaFn(delta) {
    const totalPags = Math.ceil(contatosFiltrados.length / contatosPerPage);
    const newPage = contatosPagina + delta;
    if (newPage >= 1 && newPage <= totalPags) {
        contatosPagina = newPage;
        renderizarContatos();
    }
}
// Alias for onclick
window.contatosPagina = contatosPaginaFn;

// ==================== TAB CHAMADAS ====================

async function carregarChamadas() {
    try {
        // Carregar stats e ligacoes
        const [statsRes, ligRes] = await Promise.all([
            fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/ligacoes/stats`),
            fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/ligacoes?page=${chamadasPage}`)
        ]);

        const statsData = await statsRes.json();
        const ligData = await ligRes.json();

        renderizarChamadasStats(statsData);
        renderizarChamadasTabela(ligData);
    } catch (error) {
        console.error('Erro chamadas:', error);
        document.getElementById('chamadasBody').innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center text-red-400"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar chamadas</td></tr>`;
    }
}

function renderizarChamadasStats(data) {
    const stats = data.estatisticas_gerais || data.stats || data || {};
    const resumo = stats.resumo || {};
    const progresso = stats.progresso || {};

    document.getElementById('callsTotal').textContent = resumo.total_ligacoes || data.total || 0;
    document.getElementById('callsSuccess').textContent = progresso.sucesso || data.sucesso || 0;
    document.getElementById('callsFailed').textContent = progresso.falhas || data.falhas || 0;
    document.getElementById('callsAvgDuration').textContent = resumo.duracao_media_geral ? Math.round(resumo.duracao_media_geral) + 's' : '0s';
}

function renderizarChamadasTabela(data) {
    const tbody = document.getElementById('chamadasBody');
    const ligacoes = data.ligacoes_paginadas?.data || data.data || data.ligacoes || [];

    if (!ligacoes || ligacoes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-inbox text-xl"></i><p class="mt-2 text-sm">Nenhuma chamada encontrada</p></td></tr>`;
        return;
    }

    tbody.innerHTML = ligacoes.map(item => {
        const ch = item.call_history;
        const callId = item.call_id_retell || item.id;
        const telefone = item.contato?.telefone || item.telefone || 'N/A';
        const cliente = item.contato?.nome || 'N/A';
        const duracao = ch?.duration_ms ? (ch.duration_ms / 1000).toFixed(0) : (item.duracao || 0);
        const status = ch?.call_status || item.status || 'registered';
        const badgeClass = (status === 'ended' || status === 'completed') ? 'bg-emerald-100 text-emerald-700'
            : (status === 'error' || status === 'failed') ? 'bg-red-100 text-red-700'
            : 'bg-amber-100 text-amber-700';
        const statusLabel = (status === 'ended' || status === 'completed') ? 'Finalizada'
            : (status === 'error' || status === 'failed') ? 'Erro' : 'Registrada';
        const sentimento = ch?.user_sentiment || item.user_sentiment || '-';

        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-3 text-sm text-slate-500">#${item.id}</td>
                <td class="px-6 py-3 text-sm text-slate-600 font-mono">${telefone}</td>
                <td class="px-6 py-3 text-sm text-slate-900 font-medium">${escapeHtml(cliente)}</td>
                <td class="px-6 py-3 text-sm text-slate-600">${duracao > 0 ? duracao + 's' : '-'}</td>
                <td class="px-6 py-3 text-sm text-slate-600">${sentimento}</td>
                <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${statusLabel}</span></td>
                <td class="px-6 py-3 text-sm text-slate-500">${new Date(item.created_at).toLocaleDateString('pt-BR')}</td>
                <td class="px-6 py-3"><button class="px-3 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors" onclick="viewCallDetails('${callId}')">Ver</button></td>
            </tr>
        `;
    }).join('');

    // Paginacao info
    const pag = data.ligacoes_paginadas?.pagination || data.pagination || {};
    if (pag.current_page) {
        document.getElementById('chamadasPagInfo').textContent = `Pagina ${pag.current_page} de ${pag.last_page || 1}`;
    }
}

function chamadasPaginaFn(delta) {
    chamadasPage += delta;
    if (chamadasPage < 1) chamadasPage = 1;
    carregarChamadas();
}
window.chamadasPagina = chamadasPaginaFn;

// ==================== CALL DETAILS MODAL ====================

function getAudioType(url) {
    if (!url) return 'audio/mpeg';
    const ext = url.split('.').pop().toLowerCase();
    const types = { mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', m4a: 'audio/mp4' };
    return types[ext] || 'audio/mpeg';
}

async function viewCallDetails(callId) {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/ura/history/call/${callId}`);
        const data = await response.json();
        if (data.success && data.data) {
            showCallModal(data.data, callId);
        } else {
            showToast('Erro ao carregar detalhes', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro ao carregar detalhes', 'error');
    }
}

function showCallModal(callData, callId) {
    const localData = callData.local_data || {};
    const analysis = callData.call_analysis || {};
    const summary = analysis.call_summary || {};

    const modal = document.createElement('div');
    modal.id = 'detailsModal';
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';

    const content = document.createElement('div');
    content.className = 'bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl';
    content.innerHTML = `
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-lg font-bold text-slate-900"><i class="fas fa-phone text-slate-400 mr-2"></i>Detalhes da Ligacao</h2>
            <button onclick="closeCallModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors text-xl">&times;</button>
        </div>
        <div class="p-6 space-y-6">
            ${localData.cliente_nome ? `
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-3"><i class="fas fa-user mr-2"></i>Cliente</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-blue-600">Nome:</span> <span class="font-medium text-blue-900">${localData.cliente_nome}</span></div>
                    <div><span class="text-blue-600">Empresa:</span> <span class="font-medium text-blue-900">${localData.empresa_credora || '-'}</span></div>
                    <div><span class="text-blue-600">Valor:</span> <span class="font-medium text-blue-900">R$ ${parseFloat(localData.valor_devido || 0).toFixed(2)}</span></div>
                    <div><span class="text-blue-600">Vencimento:</span> <span class="font-medium text-blue-900">${localData.vencimento || '-'}</span></div>
                </div>
            </div>` : ''}

            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-info-circle text-slate-400 mr-2"></i>Info</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-slate-50 rounded-lg p-3"><p class="text-[11px] uppercase text-slate-400 font-medium">Call ID</p><p class="text-sm font-medium text-slate-900 break-all mt-0.5">${callId}</p></div>
                    <div class="bg-slate-50 rounded-lg p-3"><p class="text-[11px] uppercase text-slate-400 font-medium">Status</p><p class="text-sm font-medium text-slate-900 mt-0.5">${callData.call_status || 'N/A'}</p></div>
                    <div class="bg-slate-50 rounded-lg p-3"><p class="text-[11px] uppercase text-slate-400 font-medium">Duracao</p><p class="text-sm font-medium text-slate-900 mt-0.5">${callData.enhanced_data?.duration_formatted || (summary.duration_ms ? (summary.duration_ms / 1000).toFixed(0) + 's' : 'N/A')}</p></div>
                </div>
            </div>

            ${callData.user_sentiment ? `
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-chart-pie text-slate-400 mr-2"></i>Analise</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-lg p-3"><p class="text-[11px] uppercase text-slate-400 font-medium">Sentimento</p><p class="text-sm font-medium text-slate-900 mt-0.5">${callData.user_sentiment}</p></div>
                    <div class="bg-slate-50 rounded-lg p-3"><p class="text-[11px] uppercase text-slate-400 font-medium">Sucesso</p><p class="text-sm font-medium text-slate-900 mt-0.5">${callData.call_successful ? 'Sim' : 'Nao'}</p></div>
                </div>
            </div>` : ''}

            ${summary.summary ? `
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-file-alt text-slate-400 mr-2"></i>Resumo</h3>
                <div class="bg-slate-50 rounded-lg p-4 text-sm text-slate-700 leading-relaxed">${summary.summary}</div>
            </div>` : ''}

            ${callData.transcript ? `
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-closed-captioning text-slate-400 mr-2"></i>Transcricao</h3>
                <div class="bg-slate-50 rounded-lg p-4 text-xs text-slate-700 leading-relaxed max-h-64 overflow-y-auto">${callData.transcript.split('\\n').join('<br />')}</div>
            </div>` : ''}

            ${callData.recording_url ? `
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3"><i class="fas fa-microphone text-slate-400 mr-2"></i>Gravacao</h3>
                <audio controls class="w-full rounded-lg" preload="metadata">
                    <source src="${callData.recording_url}" type="${getAudioType(callData.recording_url)}">
                </audio>
            </div>` : ''}
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex justify-end">
            <button onclick="closeCallModal()" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">Fechar</button>
        </div>
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);
    modal.onclick = (e) => { if (e.target === modal) closeCallModal(); };
}

function closeCallModal() {
    const modal = document.getElementById('detailsModal');
    if (modal) modal.remove();
}

// ==================== TAB IMPORTAR ====================

function initImportForm() {
    document.getElementById('importFile').addEventListener('change', previewImportFile);
    document.getElementById('importFormTab').addEventListener('submit', async (e) => {
        e.preventDefault();
        await importarContatosTab();
    });
}

async function previewImportFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    convertedCsvFile = null;

    if (file.size > 10 * 1024 * 1024) {
        showImportAlert('Arquivo muito grande. Maximo 10MB', 'error');
        return;
    }

    try {
        let csvText;
        if (isExcelFile(file)) {
            const data = await file.arrayBuffer();
            const wb = XLSX.read(data, { type: 'array' });
            csvText = XLSX.utils.sheet_to_csv(wb.Sheets[wb.SheetNames[0]]);
            const blob = new Blob([csvText], { type: 'text/csv' });
            convertedCsvFile = new File([blob], file.name.replace(/\.(xlsx|xls)$/i, '.csv'), { type: 'text/csv' });
        } else {
            csvText = await file.text();
        }

        const lines = csvText.split('\n');
        const headers = lines[0].split(',').map(h => h.trim());
        document.getElementById('importPreviewHead').innerHTML = '<tr>' + headers.map(h => `<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">${h}</th>`).join('') + '</tr>';
        let bodyHtml = '';
        for (let i = 1; i < Math.min(6, lines.length); i++) {
            if (lines[i].trim()) {
                const cells = lines[i].split(',').map(c => c.trim());
                bodyHtml += '<tr>' + cells.map(c => `<td class="px-4 py-2 text-sm text-slate-600">${c}</td>`).join('') + '</tr>';
            }
        }
        document.getElementById('importPreviewBody').innerHTML = bodyHtml;
        document.getElementById('importPreviewContainer').classList.remove('hidden');
    } catch (error) {
        showImportAlert('Erro ao ler arquivo: ' + error.message, 'error');
    }
}

function isExcelFile(file) {
    const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
    return ['.xlsx', '.xls'].includes(ext);
}

async function importarContatosTab() {
    const fileInput = document.getElementById('importFile');
    const file = fileInput.files[0];
    if (!file) { showImportAlert('Selecione um arquivo', 'error'); return; }

    const arquivo = convertedCsvFile || file;
    const formData = new FormData();
    formData.append('mailing_id', campanhaId);
    formData.append('arquivo', arquivo);

    const btn = document.getElementById('importSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Importando...';

    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${campanhaId}/importar`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok) {
            showImportAlert('Importacao concluida com sucesso!', 'success');
            document.getElementById('importFormTab').reset();
            document.getElementById('importPreviewContainer').classList.add('hidden');
            // Reload contatos e resumo
            contatosLoaded = false;
            carregarCampanha();
        } else {
            showImportAlert(data.message || 'Erro na importacao', 'error');
        }
    } catch (error) {
        showImportAlert('Erro: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Importar Contatos';
    }
}

function showImportAlert(message, type) {
    const container = document.getElementById('importAlertContainer');
    const cls = type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    container.innerHTML = `<div class="p-3 ${cls} border rounded-lg text-sm flex items-center gap-2"><i class="fas fa-${icon}"></i><span>${message}</span></div>`;
    setTimeout(() => container.innerHTML = '', 5000);
}

// ==================== UTILS ====================

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function maskCPF(cpf) {
    if (!cpf || cpf.length < 11) return cpf || 'N/A';
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showToast(message, type) {
    if (typeof NotificacaoManager !== 'undefined') {
        type === 'success' ? NotificacaoManager.sucesso(message) : NotificacaoManager.erro(message);
        return;
    }
    // Fallback simple toast
    const toast = document.createElement('div');
    const cls = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
    toast.className = `fixed top-4 right-4 z-50 ${cls} text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
