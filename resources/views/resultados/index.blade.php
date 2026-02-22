@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">📊 Resultados de Importação</h1>
            <p class="text-muted">Acompanhe os resultados das campanhas importadas</p>
        </div>
    </div>

    @if($error ?? null)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($mailings->isEmpty())
    <div class="alert alert-info">
        <strong>Nenhuma campanha encontrada.</strong> Importe um CSV para começar.
    </div>
    @else
    <div class="row">
        @foreach($mailings as $mailing)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">{{ $mailing->nome }}</h5>
                    <small class="text-light">{{ $mailing->script->nome ?? 'Sem script' }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 text-primary">{{ $mailing->stats['total_importados'] }}</div>
                                <small class="text-muted">Total Importados</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 text-success">{{ $mailing->stats['acordos'] }}</div>
                                <small class="text-muted">Acordos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 text-info">{{ $mailing->stats['taxa_resposta'] }}</div>
                                <small class="text-muted">Taxa Resposta</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 text-warning">{{ $mailing->stats['taxa_conversao'] }}</div>
                                <small class="text-muted">Taxa Conversão</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted d-block">
                            Pendentes: {{ $mailing->stats['pendentes'] }} |
                            Falhados: {{ $mailing->stats['falhados'] }}
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="{{ route('resultados.show', $mailing->id) }}" class="btn btn-sm btn-primary">
                        Ver Detalhes
                    </a>
                    <a href="{{ route('resultados.exportar', $mailing->id) }}" class="btn btn-sm btn-outline-secondary">
                        📥 Exportar CSV
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Paginação -->
    <div class="mt-4">
        {{ $mailings->links() }}
    </div>
    @endif
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    }
</style>
@endsection
