@extends('layouts.app')

@section('title', 'Contatos - URA')

@section('content')
<div class="container-fluid" style="padding: 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">
            <i class="fas fa-users"></i> Contatos
        </h2>
    </div>

    <!-- Filters -->
    <div class="notion-card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Buscar por nome ou telefone..."
                       value="{{ request('search') }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="min-width: 150px;">
                <select name="mailing_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Todas as Campanhas</option>
                    @foreach($campanhas ?? [] as $camp)
                    <option value="{{ $camp->id }}" {{ request('mailing_id') == $camp->id ? 'selected' : '' }}>
                        {{ $camp->nome }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 150px;">
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Todos os Status</option>
                    <option value="pendente" {{ request('status') == 'pendente' ? 'selected' : '' }}>Pendente</option>
                    <option value="chamando" {{ request('status') == 'chamando' ? 'selected' : '' }}>Chamando</option>
                    <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                </select>
            </div>
            <button type="submit" class="notion-btn-secondary" style="padding: 8px 20px;">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Contacts Table -->
    <div class="notion-card">
        <div style="overflow-x: auto;">
            <table class="notion-table" style="width: 100%;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 12px; text-align: left;">Nome</th>
                        <th style="padding: 12px; text-align: left;">Telefone</th>
                        <th style="padding: 12px; text-align: left;">Email</th>
                        <th style="padding: 12px; text-align: left;">Campanha</th>
                        <th style="padding: 12px; text-align: left;">Valor</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Última Chamada</th>
                        <th style="padding: 12px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contatos ?? [] as $contato)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 12px;">
                            <strong>{{ $contato->nome }}</strong>
                        </td>
                        <td style="padding: 12px;">
                            <code style="background-color: #f3f4f6; padding: 2px 6px; border-radius: 3px;">
                                {{ $contato->telefone }}
                            </code>
                        </td>
                        <td style="padding: 12px; color: #666; font-size: 13px;">
                            {{ $contato->email ?? '-' }}
                        </td>
                        <td style="padding: 12px;">
                            <small style="color: #666;">{{ $contato->mailing->nome ?? 'N/A' }}</small>
                        </td>
                        <td style="padding: 12px; font-weight: bold;">
                            R$ {{ number_format($contato->valor_debito, 2, ',', '.') }}
                        </td>
                        <td style="padding: 12px;">
                            <span style="
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: bold;
                                @if($contato->status == 'pendente')
                                    background-color: #fef3c7;
                                    color: #92400e;
                                @elseif($contato->status == 'chamando')
                                    background-color: #bfdbfe;
                                    color: #1e40af;
                                @elseif($contato->status == 'concluido')
                                    background-color: #d1fae5;
                                    color: #065f46;
                                @else
                                    background-color: #e5e7eb;
                                    color: #374151;
                                @endif
                            ">
                                {{ ucfirst($contato->status) }}
                            </span>
                        </td>
                        <td style="padding: 12px; color: #666; font-size: 13px;">
                            @if($contato->ultima_chamada_em)
                                {{ $contato->ultima_chamada_em->format('d/m/Y H:i') }}
                            @else
                                Nunca
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($contato->status != 'concluido')
                            <button onclick="callContact({{ $contato->id }})" class="notion-btn-icon"
                                    title="Chamar agora">
                                <i class="fas fa-phone"></i>
                            </button>
                            @endif
                            <a href="{{ route('contatos.detalhes', $contato->id) }}" class="notion-btn-icon"
                               title="Detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            Nenhum contato encontrado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($contatos) && $contatos->hasPages())
    <div style="margin-top: 20px; text-align: center;">
        {{ $contatos->links() }}
    </div>
    @endif

</div>

<style>
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
function callContact(id) {
    if (confirm('Iniciar chamada para este contato?')) {
        fetch(`/api/ura/call/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ contato_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Chamada iniciada com sucesso! ID: ' + data.data.call_id);
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Erro ao iniciar chamada: ' + data.message);
            }
        })
        .catch(err => alert('Erro na requisição'));
    }
}
</script>
@endsection
