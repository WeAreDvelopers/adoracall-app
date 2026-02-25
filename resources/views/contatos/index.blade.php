@extends('layouts.app')

@section('title', 'Contatos Importados')

@section('content')
<div class="container-fluid" style="padding: 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #1f2937; font-size: 24px;">
            <i class="fas fa-address-book"></i> Contatos Importados
        </h2>
        <span style="color: #6b7280; font-size: 14px;">
            {{ $stats->total ?? 0 }} contatos encontrados
        </span>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="notion-card" style="padding: 16px;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Total</div>
            <div style="font-size: 28px; font-weight: 700; color: #1f2937;">{{ number_format($stats->total ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="notion-card" style="padding: 16px;">
            <div style="font-size: 12px; color: #92400e; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Pendentes</div>
            <div style="font-size: 28px; font-weight: 700; color: #92400e;">{{ number_format($stats->pendentes ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="notion-card" style="padding: 16px;">
            <div style="font-size: 12px; color: #1e40af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Chamando</div>
            <div style="font-size: 28px; font-weight: 700; color: #1e40af;">{{ number_format($stats->chamando ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="notion-card" style="padding: 16px;">
            <div style="font-size: 12px; color: #065f46; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Concluídos</div>
            <div style="font-size: 28px; font-weight: 700; color: #065f46;">{{ number_format($stats->concluidos ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="notion-card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET" action="{{ route('contatos.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Buscar</label>
                <input type="text" name="search" placeholder="Nome, telefone ou CPF..."
                       value="{{ request('search') }}"
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="min-width: 180px;">
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Campanha</label>
                <select name="mailing_id" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    <option value="">Todas</option>
                    @foreach($mailings as $mailing)
                    <option value="{{ $mailing->id }}" {{ request('mailing_id') == $mailing->id ? 'selected' : '' }}>
                        {{ $mailing->nome }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Status</label>
                <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    <option value="">Todos</option>
                    <option value="pendente" {{ request('status') == 'pendente' ? 'selected' : '' }}>Pendente</option>
                    <option value="chamando" {{ request('status') == 'chamando' ? 'selected' : '' }}>Chamando</option>
                    <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                </select>
            </div>
            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">Resultado</label>
                <select name="resultado" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    <option value="">Todos</option>
                    @foreach($resultados as $resultado)
                    <option value="{{ $resultado }}" {{ request('resultado') == $resultado ? 'selected' : '' }}>
                        {{ ucfirst($resultado) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="notion-btn-primary" style="padding: 8px 20px;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                @if(request()->hasAny(['search', 'mailing_id', 'status', 'resultado']))
                <a href="{{ route('contatos.index') }}" class="notion-btn-secondary" style="padding: 8px 16px; text-decoration: none;">
                    <i class="fas fa-times"></i> Limpar
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Contacts Table -->
    <div class="notion-card">
        <div style="overflow-x: auto;">
            <table class="notion-table" style="width: 100%;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Nome</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Telefone</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">CPF</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Campanha</th>
                        <th style="padding: 12px; text-align: right; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Valor</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Vencimento</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Status</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Resultado</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Tentativas</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Última Tentativa</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contatos as $contato)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 12px;">
                            <strong style="color: #1f2937;">{{ $contato->nome_completo }}</strong>
                        </td>
                        <td style="padding: 12px;">
                            <code style="background-color: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 13px;">
                                {{ $contato->telefone }}
                            </code>
                        </td>
                        <td style="padding: 12px; color: #6b7280; font-size: 13px;">
                            {{ $contato->cpf_primeiros_digitos ? $contato->cpf_primeiros_digitos . '...' : '-' }}
                        </td>
                        <td style="padding: 12px;">
                            <span style="font-size: 13px; color: #6b7280;">{{ $contato->mailing->nome ?? 'N/A' }}</span>
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: 600; color: #1f2937;">
                            @if($contato->valor_debito)
                                R$ {{ number_format($contato->valor_debito, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 12px; color: #6b7280; font-size: 13px;">
                            @if($contato->vencimento)
                                {{ $contato->vencimento->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="
                                padding: 4px 10px;
                                border-radius: 9999px;
                                font-size: 12px;
                                font-weight: 600;
                                @if($contato->status == 'pendente')
                                    background-color: #fef3c7; color: #92400e;
                                @elseif($contato->status == 'chamando')
                                    background-color: #dbeafe; color: #1e40af;
                                @elseif($contato->status == 'concluido')
                                    background-color: #d1fae5; color: #065f46;
                                @else
                                    background-color: #f3f4f6; color: #374151;
                                @endif
                            ">
                                {{ ucfirst($contato->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($contato->resultado)
                            <span style="
                                padding: 4px 10px;
                                border-radius: 9999px;
                                font-size: 12px;
                                font-weight: 600;
                                background-color: #ede9fe;
                                color: #6d28d9;
                            ">
                                {{ ucfirst($contato->resultado) }}
                            </span>
                            @else
                                <span style="color: #d1d5db;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                            {{ $contato->tentativas ?? 0 }}
                        </td>
                        <td style="padding: 12px; color: #6b7280; font-size: 13px;">
                            @if($contato->ultima_tentativa)
                                {{ $contato->ultima_tentativa->format('d/m/Y H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('contatos.show', ['id' => $contato->id]) }}" class="notion-btn-icon" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" style="padding: 40px; text-align: center; color: #9ca3af;">
                            <i class="fas fa-inbox" style="font-size: 36px; margin-bottom: 12px; display: block;"></i>
                            Nenhum contato encontrado
                            @if(request()->hasAny(['search', 'mailing_id', 'status', 'resultado']))
                                <br><a href="{{ route('contatos.index') }}" style="color: #2563eb; font-size: 13px;">Limpar filtros</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($contatos->hasPages())
    <div style="margin-top: 20px; display: flex; justify-content: center;">
        {{ $contatos->links() }}
    </div>
    @endif

</div>

<style>
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

.notion-btn-primary {
    padding: 8px 16px;
    background-color: #2563eb;
    color: white;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notion-btn-primary:hover {
    background-color: #1d4ed8;
}

.notion-btn-secondary {
    padding: 8px 16px;
    background-color: #f3f4f6;
    color: #374151;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
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
    font-size: 14px;
}

.notion-btn-icon:hover {
    background-color: #e5e7eb;
    text-decoration: none;
}
</style>
@endsection
