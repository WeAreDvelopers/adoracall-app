@extends('layouts.app')

@section('title', 'Campanhas - URA')

@section('content')
<div class="container-fluid" style="padding: 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">
            <i class="fas fa-bullhorn"></i> Campanhas
        </h2>
        <a href="{{ route('campanhas.criar') }}" class="notion-btn-primary">
            <i class="fas fa-plus"></i> Nova Campanha
        </a>
    </div>

    <!-- Filters -->
    <div class="notion-card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Buscar por nome..."
                       value="{{ request('search') }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="min-width: 150px;">
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Todos os Status</option>
                    <option value="ativa" {{ request('status') == 'ativa' ? 'selected' : '' }}>Ativa</option>
                    <option value="pausada" {{ request('status') == 'pausada' ? 'selected' : '' }}>Pausada</option>
                    <option value="concluida" {{ request('status') == 'concluida' ? 'selected' : '' }}>Concluída</option>
                </select>
            </div>
            <button type="submit" class="notion-btn-secondary" style="padding: 8px 20px;">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Campaigns Table -->
    <div class="notion-card">
        <div style="overflow-x: auto;">
            <table class="notion-table" style="width: 100%;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 12px; text-align: left;">Nome</th>
                        <th style="padding: 12px; text-align: left;">Tipo</th>
                        <th style="padding: 12px; text-align: left;">Contatos</th>
                        <th style="padding: 12px; text-align: left;">Chamadas</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Criada</th>
                        <th style="padding: 12px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campanhas ?? [] as $campanha)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 12px;">
                            <strong>{{ $campanha->nome }}</strong>
                        </td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; background-color: #ede9fe; color: #6d28d9; border-radius: 4px; font-size: 12px;">
                                {{ ucfirst($campanha->tipo) }}
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <strong>{{ $campanha->total_contatos ?? 0 }}</strong>
                        </td>
                        <td style="padding: 12px;">
                            {{ $campanha->total_chamadas ?? 0 }}
                        </td>
                        <td style="padding: 12px;">
                            <span style="
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: bold;
                                @if($campanha->status == 'ativa')
                                    background-color: #d1fae5;
                                    color: #065f46;
                                @elseif($campanha->status == 'pausada')
                                    background-color: #fed7aa;
                                    color: #92400e;
                                @else
                                    background-color: #e5e7eb;
                                    color: #374151;
                                @endif
                            ">
                                {{ ucfirst($campanha->status) }}
                            </span>
                        </td>
                        <td style="padding: 12px; color: #666; font-size: 13px;">
                            {{ $campanha->created_at->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('campanhas.editar', $campanha->id) }}" class="notion-btn-icon"
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($campanha->status == 'ativa')
                                <button onclick="pauseCampaign({{ $campanha->id }})" class="notion-btn-icon"
                                        title="Pausar">
                                    <i class="fas fa-pause"></i>
                                </button>
                            @elseif($campanha->status == 'pausada')
                                <button onclick="activateCampaign({{ $campanha->id }})" class="notion-btn-icon"
                                        title="Ativar">
                                    <i class="fas fa-play"></i>
                                </button>
                            @endif
                            <a href="{{ route('campanhas.detalhes', $campanha->id) }}" class="notion-btn-icon"
                               title="Detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            Nenhuma campanha encontrada
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($campanhas) && $campanhas->hasPages())
    <div style="margin-top: 20px; text-align: center;">
        {{ $campanhas->links() }}
    </div>
    @endif

</div>

<style>
.notion-btn-primary {
    padding: 10px 20px;
    background-color: #2563eb;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.notion-btn-primary:hover {
    background-color: #1d4ed8;
    color: white;
    text-decoration: none;
}

.notion-btn-secondary {
    padding: 8px 16px;
    background-color: #f3f4f6;
    color: #374151;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    cursor: pointer;
    font-weight: 500;
}

.notion-btn-secondary:hover {
    background-color: #e5e7eb;
}

.notion-btn-icon {
    padding: 6px 10px;
    background-color: #f3f4f6;
    color: #2563eb;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    margin: 0 2px;
    display: inline-block;
    text-decoration: none;
}

.notion-btn-icon:hover {
    background-color: #e5e7eb;
    text-decoration: none;
}

.notion-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.notion-table {
    width: 100%;
    font-size: 14px;
    border-collapse: collapse;
}

.notion-table td {
    padding: 12px;
}

h2 {
    margin: 0;
    color: #1f2937;
    font-size: 24px;
}
</style>

<script>
function activateCampaign(id) {
    if (confirm('Ativar esta campanha?')) {
        fetch(`/api/filas_campanha/${id}/ativar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        }).then(() => location.reload())
          .catch(err => alert('Erro ao ativar campanha'));
    }
}

function pauseCampaign(id) {
    if (confirm('Pausar esta campanha?')) {
        fetch(`/api/filas_campanha/${id}/pausar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        }).then(() => location.reload())
          .catch(err => alert('Erro ao pausar campanha'));
    }
}
</script>
@endsection
