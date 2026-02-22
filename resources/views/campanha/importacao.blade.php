@extends('layouts.app')

@section('title', 'Importar Contatos - URA Dvelopers')

@section('content')
<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Importar Contatos</h1>
    <p class="text-slate-500 text-sm mt-1">Fa&ccedil;a upload de um arquivo CSV com os contatos para importa&ccedil;&atilde;o</p>
</div>

<div id="alertContainer" class="mb-4"></div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="p-6">
        <form id="importForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="mailing_id">Campanha <span class="text-red-500">*</span></label>
                <select class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" id="mailing_id" name="mailing_id" required>
                    <option value="">-- Carregando campanhas --</option>
                </select>
                <p class="text-xs text-slate-400 mt-1">Selecione a campanha onde os contatos ser&atilde;o importados</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="arquivo">Arquivo CSV ou Excel <span class="text-red-500">*</span></label>
                <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" type="file" id="arquivo" name="arquivo" accept=".csv,.xlsx,.xls" required>
                <div class="flex items-center gap-4 mt-2">
                    <p class="text-xs text-slate-400">Aceita arquivos .csv ou .xlsx at&eacute; 10MB</p>
                    <button type="button" onclick="baixarModeloXLSX()" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium transition-colors">
                        <i class="fas fa-file-excel"></i> Baixar modelo Excel
                    </button>
                    <a href="/templates/modelo_contatos.csv" download class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-download"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Colunas aceitas -->
            <div class="bg-slate-50 rounded-lg border border-slate-200 p-4">
                <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Colunas do CSV</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                        <span class="text-xs text-slate-700 font-medium">nome</span>
                        <span class="text-[10px] text-red-500">*</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                        <span class="text-xs text-slate-700 font-medium">telefone</span>
                        <span class="text-[10px] text-red-500">*</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                        <span class="text-xs text-slate-500">cpf</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                        <span class="text-xs text-slate-500">valor_debito</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                        <span class="text-xs text-slate-500">vencimento</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0"></span>
                        <span class="text-xs text-slate-500">data_nascimento</span>
                    </div>
                </div>
                <p class="text-[10px] text-slate-400 mt-2"><span class="text-red-500">*</span> Obrigat&oacute;rias &middot; Datas: DD/MM/AAAA ou AAAA-MM-DD &middot; Telefone com DDD (ex: 11999887766)</p>
            </div>

            <!-- Preview -->
            <div id="previewContainer" class="hidden pt-6 border-t border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Preview do Arquivo</h3>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200" id="previewTable">
                        <thead class="bg-slate-50" id="previewHead"></thead>
                        <tbody class="divide-y divide-slate-100" id="previewBody"></tbody>
                    </table>
                </div>
                <div id="previewErrors" class="text-red-500 text-xs mt-2"></div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 justify-end pt-6 border-t border-slate-100">
                <button type="button" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors" onclick="resetForm()">
                    <i class="fas fa-undo mr-1"></i> Limpar
                </button>
                <button type="button" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors" onclick="irFila()">
                    <i class="fas fa-list mr-1"></i> Gerenciar Filas
                </button>
                <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-lg text-sm transition-colors shadow-sm" id="submitBtn">
                    <i class="fas fa-upload mr-1"></i> Importar Contatos
                </button>
            </div>
        </form>

        <!-- Import Status -->
        <div id="importStatus" class="hidden mt-6 pt-6 border-t border-slate-100">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm flex items-center gap-2">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Processando importa&ccedil;&atilde;o...</span>
            </div>
            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-4">
                <div class="h-full bg-brand-500 rounded-full transition-all duration-300" id="progressBar" style="width: 0%"></div>
            </div>
            <p id="progressText" class="text-xs text-slate-400 mt-2"></p>
        </div>
    </div>
</div>
@endsection

@push('head-scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
@endpush

@push('scripts')
<script>
function baixarModeloXLSX() {
    const headers = ['nome', 'telefone', 'cpf', 'valor_debito', 'vencimento', 'data_nascimento'];
    const dados = [
        ['Jo\u00e3o da Silva',    '11999887766', '12345678901', 1500.00,  '15/01/2026', '10/05/1985'],
        ['Maria Oliveira',  '21988776655', '98765432100', 2300.50,  '20/02/2026', '22/08/1990'],
        ['Carlos Santos',   '31977665544', '',            850.00,   '10/03/2026', ''],
        ['Ana Souza',       '41966554433', '11122233344', 4200.00,  '05/04/2026', '15/12/1978'],
        ['Pedro Lima',      '51955443322', '',            0,        '',           ''],
    ];

    const ws_data = [headers, ...dados];
    const ws = XLSX.utils.aoa_to_sheet(ws_data);

    // Larguras das colunas
    ws['!cols'] = [
        { wch: 22 }, // nome
        { wch: 16 }, // telefone
        { wch: 14 }, // cpf
        { wch: 14 }, // valor_debito
        { wch: 14 }, // vencimento
        { wch: 16 }, // data_nascimento
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Contatos');
    XLSX.writeFile(wb, 'modelo_contatos.xlsx');
}

document.addEventListener('DOMContentLoaded', async function() {
    await carregarCampanhas();
    document.getElementById('arquivo').addEventListener('change', previewCSV);
    document.getElementById('importForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await importarContatos();
    });
});

async function carregarCampanhas() {
    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha`);
        const data = await response.json();
        const select = document.getElementById('mailing_id');
        select.innerHTML = '<option value="">-- Selecione uma campanha --</option>';
        if (data.data && Array.isArray(data.data)) {
            data.data.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = c.nome;
                select.appendChild(option);
            });
        }
    } catch (error) {
        showAlert('Erro ao carregar campanhas', 'error');
    }
}

// Variável global para guardar o CSV convertido (quando o arquivo é XLSX)
let convertedCsvFile = null;

function isExcelFile(file) {
    const excelExts = ['.xlsx', '.xls'];
    const excelMimes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];
    const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
    return excelExts.includes(ext) || excelMimes.includes(file.type);
}

function xlsxToCsvText(workbook) {
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
    return XLSX.utils.sheet_to_csv(firstSheet);
}

async function previewCSV(e) {
    const file = e.target.files[0];
    if (!file) return;

    convertedCsvFile = null;

    if (file.size > 10 * 1024 * 1024) {
        showAlert('Arquivo muito grande. Máximo 10MB', 'error');
        return;
    }

    try {
        let csvText;

        if (isExcelFile(file)) {
            // Converter XLSX para CSV usando a lib XLSX já carregada
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            csvText = xlsxToCsvText(workbook);

            // Criar um File CSV a partir do XLSX convertido para enviar ao backend
            const blob = new Blob([csvText], { type: 'text/csv' });
            const nomeBase = file.name.replace(/\.(xlsx|xls)$/i, '');
            convertedCsvFile = new File([blob], nomeBase + '.csv', { type: 'text/csv' });
        } else {
            csvText = await file.text();
        }

        // Exibir preview
        const lines = csvText.split('\n');
        const headers = lines[0].split(',').map(h => h.trim());
        const previewContainer = document.getElementById('previewContainer');
        const previewHead = document.getElementById('previewHead');
        const previewBody = document.getElementById('previewBody');

        previewHead.innerHTML = '<tr>' + headers.map(h => `<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">${h}</th>`).join('') + '</tr>';
        previewBody.innerHTML = '';

        for (let i = 1; i < Math.min(6, lines.length); i++) {
            if (lines[i].trim()) {
                const cells = lines[i].split(',').map(c => c.trim());
                previewBody.innerHTML += '<tr>' + cells.map(c => `<td class="px-4 py-2 text-sm text-slate-600">${c}</td>`).join('') + '</tr>';
            }
        }
        previewContainer.classList.remove('hidden');
    } catch (error) {
        showAlert('Erro ao ler arquivo: ' + error.message, 'error');
    }
}

async function importarContatos() {
    const mailingId = document.getElementById('mailing_id').value;
    const arquivoOriginal = document.getElementById('arquivo').files[0];

    if (!mailingId || !arquivoOriginal) {
        showAlert('Selecione uma campanha e um arquivo', 'error');
        return;
    }

    // Se era XLSX, envia o CSV convertido; senão envia o original
    const arquivo = convertedCsvFile || arquivoOriginal;

    const formData = new FormData();
    formData.append('mailing_id', mailingId);
    formData.append('arquivo', arquivo);

    document.getElementById('importStatus').classList.remove('hidden');
    document.getElementById('submitBtn').disabled = true;

    try {
        const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha/${mailingId}/importar`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (response.ok) {
            showAlert('Importa\u00e7\u00e3o iniciada com sucesso!', 'success');
            setTimeout(() => resetForm(), 1500);
        } else {
            showAlert(data.message || 'Erro na importa\u00e7\u00e3o', 'error');
        }
    } catch (error) {
        showAlert('Erro: ' + error.message, 'error');
    } finally {
        document.getElementById('importStatus').classList.add('hidden');
        document.getElementById('submitBtn').disabled = false;
    }
}

function resetForm() {
    document.getElementById('importForm').reset();
    document.getElementById('previewContainer').classList.add('hidden');
}

function irFila() {
    window.location.href = '/campanha/gerenciar-fila';
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const bgClass = type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    alertContainer.innerHTML = `
        <div class="p-3 ${bgClass} border rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
    `;
}
</script>
@endpush
