@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">📊 {{ $mailing->nome }}</h1>
            <p class="text-muted">
                Script: <strong>{{ $mailing->script->nome ?? 'Sem script' }}</strong> |
                Status: <span class="badge bg-info">{{ $mailing->status }}</span>
            </p>
        </div>
        <div class="col-auto">
            <a href="{{ route('resultados.index') }}" class="btn btn-outline-secondary">
                ← Voltar
            </a>
            <a href="{{ route('resultados.exportar', $mailing->id) }}" class="btn btn-outline-primary">
                📥 Exportar CSV
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Importados</h6>
                    <h3>{{ $stats['total_importados'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Acordos Realizados</h6>
                    <h3>{{ $stats['acordos'] }}</h3>
                    <small>{{ $stats['taxa_conversao'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Ligações Realizadas</h6>
                    <h3>{{ $stats['total_ligacoes'] }}</h3>
                    <small>{{ $stats['taxa_resposta'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pendentes</h6>
                    <h3>{{ $stats['pendentes'] }}</h3>
                    <small>{{ round(($stats['pendentes'] / max($stats['total_importados'], 1)) * 100, 1) }}%</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Status dos Contatos</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoStatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Distribuição de Tentativas</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoRetentativa"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if($graficoSentimento)
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Sentimento do Cliente</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoSentimento"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabela de Contatos -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Contatos Detalhados</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Ligações</th>
                        <th>Última Ligação</th>
                        <th>Resultado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contatos as $contato)
                    <tr>
                        <td>
                            <strong>{{ $contato->nome }}</strong>
                            <br>
                            <small class="text-muted">{{ $contato->cpf }}</small>
                        </td>
                        <td>{{ $contato->telefone }}</td>
                        <td>
                            @php
                                $statusClass = [
                                    'pendente' => 'secondary',
                                    'em_ligacao' => 'info',
                                    'nao_atendida' => 'warning',
                                    'acordo_realizado' => 'success',
                                    'falhou' => 'danger',
                                    'erro_ligacao' => 'danger',
                                ][$contato->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">
                                {{ $this->traduzirStatus($contato->status) ?? $contato->status }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ $contato->ligacoes()->count() }}
                            </span>
                        </td>
                        <td>
                            @php
                                $ultimaLigacao = $contato->ligacoes()->latest()->first();
                            @endphp
                            @if($ultimaLigacao)
                                {{ $ultimaLigacao->created_at->format('d/m H:i') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($ultimaLigacao && $ultimaLigacao->callHistory)
                                <small>{{ $ultimaLigacao->callHistory->resultado_conversacao ?? 'desconhecido' }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse"
                                data-bs-target="#detalhes-{{ $contato->id }}">
                                Ver
                            </button>
                        </td>
                    </tr>
                    <!-- Detalhes do Contato (colapsável) -->
                    <tr class="collapse" id="detalhes-{{ $contato->id }}">
                        <td colspan="7">
                            <div class="p-3 bg-light">
                                <strong>Informações:</strong>
                                <ul class="mb-0 small mt-2">
                                    <li>Email: {{ $contato->email ?? '-' }}</li>
                                    <li>Valor Débito: R$ {{ number_format($contato->valor_debito, 2, ',', '.') }}</li>
                                    <li>Data Vencimento: {{ $contato->vencimento ? $contato->vencimento->format('d/m/Y') : '-' }}</li>
                                    <li>Empresa: {{ $contato->empresa_credora ?? '-' }}</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Nenhum contato encontrado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $contatos->links() }}
        </div>
    </div>
</div>

<!-- Chart.js para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Status
    @if($graficoStatus)
    const statusData = {!! $graficoStatus !!};
    new Chart(document.getElementById('graficoStatus'), {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{
                data: statusData.data,
                backgroundColor: [
                    '#0d6efd', '#198754', '#dc3545', '#ffc107',
                    '#17a2b8', '#6f42c1', '#fd7e14', '#e83e8c'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    @endif

    // Gráfico de Tentativas
    @if($graficoRetentativa)
    const tentativasData = {!! $graficoRetentativa !!};
    new Chart(document.getElementById('graficoRetentativa'), {
        type: 'bar',
        data: {
            labels: tentativasData.labels,
            datasets: [{
                label: 'Contatos',
                data: tentativasData.data,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    @endif

    // Gráfico de Sentimento
    @if($graficoSentimento)
    const sentimentoData = {!! $graficoSentimento !!};
    new Chart(document.getElementById('graficoSentimento'), {
        type: 'pie',
        data: {
            labels: sentimentoData.labels,
            datasets: [{
                data: sentimentoData.data,
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6f42c1']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    @endif
});
</script>

<style>
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
</style>
@endsection
