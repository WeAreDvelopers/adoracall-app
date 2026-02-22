@extends('layouts.app')

@section('title', 'Admin - Empresas')

@section('content')
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                <i class="fas fa-building text-brand-500 mr-2"></i>Empresas
            </h1>
            <p class="text-slate-500 text-sm mt-1">Gerencie as empresas cadastradas no sistema</p>
        </div>
        <a href="/admin/empresas/criar" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
            <i class="fas fa-plus"></i> Nova Empresa
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nome, CNPJ ou email..."
                           class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           oninput="filterEmpresas()">
                </div>
            </div>
            <div class="sm:w-48">
                <select id="statusFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" onchange="filterEmpresas()">
                    <option value="">Todos os Status</option>
                    <option value="1">Ativa</option>
                    <option value="0">Inativa</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statTotal">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Ativas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statAtivas">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Inativas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statInativas">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-brand-500 uppercase tracking-wide">Usuarios</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statUsuarios">0</p>
        </div>
    </div>

    <!-- Empresas Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">
                <i class="fas fa-list text-slate-400 mr-2"></i>Lista de Empresas
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">CNPJ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Usuarios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Acoes</th>
                    </tr>
                </thead>
                <tbody id="empresasBody" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                            <p class="mt-2 text-sm">Carregando empresas...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let allEmpresas = [];

    document.addEventListener('DOMContentLoaded', function() {
        loadEmpresas();
    });

    async function loadEmpresas() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/admin/empresas`);
            const data = await response.json();

            if (response.ok) {
                allEmpresas = data.data || data || [];
                updateStats(allEmpresas);
                renderEmpresas(allEmpresas);
            } else {
                showNotification(data.message || 'Erro ao carregar empresas', 'error');
                renderEmpty();
            }
        } catch (error) {
            console.error('Erro ao carregar empresas:', error);
            showNotification('Erro ao conectar com o servidor', 'error');
            renderEmpty();
        }
    }

    function updateStats(empresas) {
        const total = empresas.length;
        const ativas = empresas.filter(e => e.ativa).length;
        const inativas = total - ativas;
        const usuarios = empresas.reduce((sum, e) => sum + (e.usuarios_count || e.total_usuarios || 0), 0);

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statAtivas').textContent = ativas;
        document.getElementById('statInativas').textContent = inativas;
        document.getElementById('statUsuarios').textContent = usuarios;
    }

    function filterEmpresas() {
        const search = document.getElementById('searchInput').value.toLowerCase().trim();
        const statusFilter = document.getElementById('statusFilter').value;

        let filtered = allEmpresas;

        if (search) {
            filtered = filtered.filter(e =>
                (e.nome || '').toLowerCase().includes(search) ||
                (e.cnpj || '').toLowerCase().includes(search) ||
                (e.email || '').toLowerCase().includes(search)
            );
        }

        if (statusFilter !== '') {
            const isAtiva = statusFilter === '1';
            filtered = filtered.filter(e => !!e.ativa === isAtiva);
        }

        renderEmpresas(filtered);
    }

    function renderEmpresas(empresas) {
        const tbody = document.getElementById('empresasBody');

        if (!empresas || empresas.length === 0) {
            renderEmpty();
            return;
        }

        tbody.innerHTML = empresas.map(empresa => {
            const statusBadge = empresa.ativa
                ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Ativa</span>'
                : '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Inativa</span>';

            const usuariosCount = empresa.usuarios_count || empresa.total_usuarios || 0;

            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-building text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900">${empresa.nome || '-'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${formatCnpj(empresa.cnpj) || '-'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${empresa.email || '-'}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 text-sm text-slate-600">
                            <i class="fas fa-users text-xs text-slate-400"></i> ${usuariosCount}
                        </span>
                    </td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="/admin/empresas/${empresa.id}/editar" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors" title="Editar">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button onclick="toggleEmpresa('${empresa.id}', ${empresa.ativa ? 'true' : 'false'})" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium ${empresa.ativa ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100'} rounded-lg transition-colors" title="${empresa.ativa ? 'Desativar' : 'Ativar'}">
                                <i class="fas fa-${empresa.ativa ? 'ban' : 'check'}"></i> ${empresa.ativa ? 'Desativar' : 'Ativar'}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderEmpty() {
        const tbody = document.getElementById('empresasBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                    <i class="fas fa-inbox text-2xl"></i>
                    <p class="mt-2 text-sm">Nenhuma empresa encontrada</p>
                </td>
            </tr>
        `;
    }

    function formatCnpj(cnpj) {
        if (!cnpj) return null;
        const digits = cnpj.replace(/\D/g, '');
        if (digits.length !== 14) return cnpj;
        return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }

    async function toggleEmpresa(id, isAtiva) {
        const action = isAtiva ? 'desativar' : 'ativar';
        if (!confirm(`Deseja realmente ${action} esta empresa?`)) return;

        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/admin/empresas/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ ativa: !isAtiva })
            });

            const data = await response.json();

            if (response.ok) {
                showNotification(`Empresa ${action === 'desativar' ? 'desativada' : 'ativada'} com sucesso!`, 'success');
                loadEmpresas();
            } else {
                showNotification(data.message || `Erro ao ${action} empresa`, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showNotification(`Erro ao ${action} empresa`, 'error');
        }
    }
</script>
@endpush
