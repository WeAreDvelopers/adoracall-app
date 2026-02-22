@extends('layouts.app')

@section('title', 'Admin - Usuarios')

@section('content')
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                <i class="fas fa-users text-brand-500 mr-2"></i>Usuarios
            </h1>
            <p class="text-slate-500 text-sm mt-1">Gerencie os usuarios e suas empresas</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nome ou email..."
                           class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           oninput="filterUsuarios()">
                </div>
            </div>
            <div class="sm:w-56">
                <select id="empresaFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" onchange="filterUsuarios()">
                    <option value="">Todas as Empresas</option>
                </select>
            </div>
            <div class="sm:w-40">
                <select id="statusFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" onchange="filterUsuarios()">
                    <option value="">Todos os Status</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
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
            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Ativos</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statAtivos">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Inativos</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statInativos">0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-brand-500 uppercase tracking-wide">Empresas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="statEmpresas">0</p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">
                <i class="fas fa-list text-slate-400 mr-2"></i>Lista de Usuarios
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Acoes</th>
                    </tr>
                </thead>
                <tbody id="usuariosBody" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                            <p class="mt-2 text-sm">Carregando usuarios...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Mover Usuario de Empresa -->
    <div id="moverModal" class="fixed inset-0 z-50 hidden">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeModal()"></div>

        <!-- Modal Content -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full relative z-10" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">
                        <i class="fas fa-exchange-alt text-brand-500 mr-2"></i>Mover Usuario
                    </h3>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-5">
                    <div class="mb-4">
                        <p class="text-sm text-slate-600">
                            Movendo <strong id="modalUserName" class="text-slate-900">-</strong> para outra empresa.
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Empresa atual: <span id="modalCurrentEmpresa" class="font-medium text-slate-700">-</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="modalEmpresaSelect">
                            Nova Empresa <span class="text-red-500">*</span>
                        </label>
                        <select id="modalEmpresaSelect" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
                            <option value="">Selecione uma empresa...</option>
                        </select>
                    </div>

                    <!-- Alert inside modal -->
                    <div id="modalAlert" class="mt-3"></div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-xl">
                    <button onclick="closeModal()" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button onclick="submitMover()" id="btnMover" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                        <i class="fas fa-exchange-alt mr-1"></i> Mover
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let allUsuarios = [];
    let allEmpresas = [];
    let currentMoverUserId = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadEmpresas();
        loadUsuarios();
    });

    async function loadEmpresas() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/admin/empresas`);
            const data = await response.json();

            if (response.ok) {
                allEmpresas = data.data || data || [];
                populateEmpresaFilters();
                document.getElementById('statEmpresas').textContent = allEmpresas.length;
            }
        } catch (error) {
            console.error('Erro ao carregar empresas:', error);
        }
    }

    function populateEmpresaFilters() {
        const filterSelect = document.getElementById('empresaFilter');
        const modalSelect = document.getElementById('modalEmpresaSelect');

        allEmpresas.forEach(empresa => {
            // Filter dropdown
            const opt1 = document.createElement('option');
            opt1.value = empresa.id;
            opt1.textContent = empresa.nome;
            filterSelect.appendChild(opt1);

            // Modal dropdown
            const opt2 = document.createElement('option');
            opt2.value = empresa.id;
            opt2.textContent = empresa.nome;
            modalSelect.appendChild(opt2);
        });
    }

    async function loadUsuarios() {
        try {
            const response = await fetchWithAuth(`${API_BASE_URL}/admin/usuarios`);
            const data = await response.json();

            if (response.ok) {
                allUsuarios = data.data || data || [];
                updateStats(allUsuarios);
                renderUsuarios(allUsuarios);
            } else {
                showNotification(data.message || 'Erro ao carregar usuarios', 'error');
                renderEmpty();
            }
        } catch (error) {
            console.error('Erro ao carregar usuarios:', error);
            showNotification('Erro ao conectar com o servidor', 'error');
            renderEmpty();
        }
    }

    function updateStats(usuarios) {
        const total = usuarios.length;
        const ativos = usuarios.filter(u => u.active || u.ativo).length;
        const inativos = total - ativos;

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statAtivos').textContent = ativos;
        document.getElementById('statInativos').textContent = inativos;
    }

    function filterUsuarios() {
        const search = document.getElementById('searchInput').value.toLowerCase().trim();
        const empresaFilter = document.getElementById('empresaFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        let filtered = allUsuarios;

        if (search) {
            filtered = filtered.filter(u =>
                (u.name || u.nome || '').toLowerCase().includes(search) ||
                (u.email || '').toLowerCase().includes(search)
            );
        }

        if (empresaFilter) {
            filtered = filtered.filter(u => {
                const empresaId = u.empresa_id || (u.empresa && u.empresa.id);
                return empresaId == empresaFilter;
            });
        }

        if (statusFilter !== '') {
            const isAtivo = statusFilter === '1';
            filtered = filtered.filter(u => {
                const active = u.active !== undefined ? u.active : u.ativo;
                return !!active === isAtivo;
            });
        }

        renderUsuarios(filtered);
    }

    function getRoleBadge(role) {
        const roleMap = {
            admin: { label: 'Admin', class: 'bg-purple-100 text-purple-700' },
            supervisor: { label: 'Supervisor', class: 'bg-blue-100 text-blue-700' },
            operador: { label: 'Operador', class: 'bg-slate-100 text-slate-700' }
        };
        const config = roleMap[role] || { label: role || 'N/A', class: 'bg-slate-100 text-slate-600' };
        return `<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${config.class}">${config.label}</span>`;
    }

    function renderUsuarios(usuarios) {
        const tbody = document.getElementById('usuariosBody');

        if (!usuarios || usuarios.length === 0) {
            renderEmpty();
            return;
        }

        tbody.innerHTML = usuarios.map(usuario => {
            const isActive = usuario.active !== undefined ? usuario.active : usuario.ativo;
            const statusBadge = isActive
                ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Ativo</span>'
                : '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Inativo</span>';

            const empresaNome = usuario.empresa
                ? usuario.empresa.nome
                : (usuario.empresa_nome || 'Sem empresa');

            const userName = usuario.name || usuario.nome || '-';
            const userEmail = usuario.email || '-';

            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0 text-sm font-medium">
                                ${userName.charAt(0).toUpperCase()}
                            </div>
                            <p class="text-sm font-medium text-slate-900">${userName}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">${userEmail}</td>
                    <td class="px-6 py-4">${getRoleBadge(usuario.role)}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 text-sm text-slate-600">
                            <i class="fas fa-building text-xs text-slate-400"></i> ${empresaNome}
                        </span>
                    </td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="openMoverModal('${usuario.id}', '${userName.replace(/'/g, "\\'")}', '${empresaNome.replace(/'/g, "\\'")}')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors" title="Mover para outra empresa">
                            <i class="fas fa-exchange-alt"></i> Mover
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderEmpty() {
        const tbody = document.getElementById('usuariosBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                    <i class="fas fa-inbox text-2xl"></i>
                    <p class="mt-2 text-sm">Nenhum usuario encontrado</p>
                </td>
            </tr>
        `;
    }

    // Modal functions
    function openMoverModal(userId, userName, currentEmpresa) {
        currentMoverUserId = userId;
        document.getElementById('modalUserName').textContent = userName;
        document.getElementById('modalCurrentEmpresa').textContent = currentEmpresa;
        document.getElementById('modalEmpresaSelect').value = '';
        document.getElementById('modalAlert').innerHTML = '';
        document.getElementById('moverModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('moverModal').classList.add('hidden');
        currentMoverUserId = null;
    }

    async function submitMover() {
        const empresaId = document.getElementById('modalEmpresaSelect').value;
        const alertDiv = document.getElementById('modalAlert');
        const btnMover = document.getElementById('btnMover');

        if (!empresaId) {
            alertDiv.innerHTML = `
                <div class="p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Selecione uma empresa de destino.</span>
                </div>
            `;
            return;
        }

        try {
            btnMover.disabled = true;
            btnMover.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Movendo...';

            const response = await fetchWithAuth(`${API_BASE_URL}/admin/usuarios/${currentMoverUserId}/empresa`, {
                method: 'PUT',
                body: JSON.stringify({ empresa_id: empresaId })
            });

            const data = await response.json();

            if (response.ok) {
                showNotification('Usuario movido com sucesso!', 'success');
                closeModal();
                loadUsuarios();
            } else {
                alertDiv.innerHTML = `
                    <div class="p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${data.message || 'Erro ao mover usuario'}</span>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erro:', error);
            alertDiv.innerHTML = `
                <div class="p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Erro ao processar requisicao</span>
                </div>
            `;
        } finally {
            btnMover.disabled = false;
            btnMover.innerHTML = '<i class="fas fa-exchange-alt mr-1"></i> Mover';
        }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('moverModal').classList.contains('hidden')) {
            closeModal();
        }
    });
</script>
@endpush
