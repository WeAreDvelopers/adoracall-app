@extends('layouts.app')

@section('title', 'Dashboard Geral - AdoraCall')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Dashboard Geral</h1>
        <p class="text-slate-500 text-sm mt-1">Vis&atilde;o geral das campanhas e m&eacute;tricas</p>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="total">0</p>
            <p class="text-xs text-slate-400 mt-1">Todas as campanhas</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Sucesso</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="success">0</p>
            <p class="text-xs text-slate-400 mt-1">Bem-sucedidas</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Falhado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="failed">0</p>
            <p class="text-xs text-slate-400 mt-1">Com falha</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-amber-500 uppercase tracking-wide">Pendente</p>
            <p class="text-2xl font-bold text-slate-900 mt-1" id="pending">0</p>
            <p class="text-xs text-slate-400 mt-1">Aguardando</p>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">
                <i class="fas fa-history text-slate-400 mr-2"></i>Atividades Recentes
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Campanha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Detalhes</th>
                    </tr>
                </thead>
                <tbody id="activitiesBody" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                            <p class="mt-2 text-sm">Carregando atividades...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
        });

        async function loadDashboard() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/dashboard/stats`);
                const data = await response.json();
                updateMetrics(data);
                loadActivities();
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function updateMetrics(data) {
            const stats = data.stats || {};
            document.getElementById('total').textContent = stats.total || 0;
            document.getElementById('success').textContent = stats.success || 0;
            document.getElementById('failed').textContent = stats.failed || 0;
            document.getElementById('pending').textContent = stats.pending || 0;
        }

        async function loadActivities() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/atividades?limit=30`);
                const data = await response.json();
                renderActivities(data);
            } catch (error) {
                console.error('Erro ao carregar atividades:', error);
            }
        }

        function renderActivities(data) {
            const tbody = document.getElementById('activitiesBody');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-inbox text-2xl"></i>
                            <p class="mt-2 text-sm">Nenhuma atividade encontrada</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = data.data.map(item => {
                const badgeClass = item.status === 'success'
                    ? 'bg-emerald-100 text-emerald-700'
                    : (item.status === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-slate-600 whitespace-nowrap">${new Date(item.created_at).toLocaleString('pt-BR')}</td>
                        <td class="px-6 py-3 text-sm text-slate-900 font-medium">${item.campanha_nome || 'N/A'}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">${item.tipo || 'N/A'}</td>
                        <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${item.status}</span></td>
                        <td class="px-6 py-3 text-sm text-slate-500">${item.descricao || '-'}</td>
                    </tr>
                `;
            }).join('');
        }
    </script>
@endpush
