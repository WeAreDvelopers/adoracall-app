@extends('layouts.app')

@section('title', 'Histórico de Chamadas - URA')

@section('content')
    <div class="container-fluid" style="padding: 20px;">

        <!-- Header -->
        <h2 style="margin: 0 0 30px 0;">
            <i class="fas fa-history"></i> Histórico de Chamadas
        </h2>

        <!-- Filters -->
        <div class="notion-card" style="margin-bottom: 20px; padding: 15px;">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <input type="date" name="data_inicio" placeholder="Data início" value="{{ request('data_inicio') }}"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <input type="date" name="data_fim" placeholder="Data fim" value="{{ request('data_fim') }}"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="min-width: 150px;">
                    <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Todos os Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Concluída
                        </option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Falha</option>
                        <option value="initiated" {{ request('status') == 'initiated' ? 'selected' : '' }}>Em andamento
                        </option>
                    </select>
                </div>
                <button type="submit" class="notion-btn-secondary" style="padding: 8px 20px;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-3">
                <div class="notion-card" style="padding: 15px; text-align: center;">
                    <div style="color: #666; font-size: 13px;">Total Chamadas</div>
                    <div style="font-size: 24px; font-weight: bold; color: #2563eb;">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="notion-card" style="padding: 15px; text-align: center;">
                    <div style="color: #666; font-size: 13px;">Concluídas</div>
                    <div style="font-size: 24px; font-weight: bold; color: #16a34a;">{{ $stats['completed'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="notion-card" style="padding: 15px; text-align: center;">
                    <div style="color: #666; font-size: 13px;">Falhas</div>
                    <div style="font-size: 24px; font-weight: bold; color: #dc2626;">{{ $stats['failed'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="notion-card" style="padding: 15px; text-align: center;">
                    <div style="color: #666; font-size: 13px;">Taxa Sucesso</div>
                    <div style="font-size: 24px; font-weight: bold; color: #7c3aed;">
                        {{ $stats['success_rate'] ?? 0 }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Calls Table -->
        <div class="notion-card">
            <div style="overflow-x: auto;">
                <table class="notion-table" style="width: 100%;">
                    <thead>
                        <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                            <th style="padding: 12px; text-align: left;">Contato</th>
                            <th style="padding: 12px; text-align: left;">Campanha</th>
                            <th style="padding: 12px; text-align: left;">Telefone</th>
                            <th style="padding: 12px; text-align: left;">Data/Hora</th>
                            <th style="padding: 12px; text-align: left;">Duração</th>
                            <th style="padding: 12px; text-align: left;">Resultado</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                            <th style="padding: 12px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chamadas ?? [] as $chamada)
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px;">
                                    <strong>{{ $chamada->contato->nome ?? 'N/A' }}</strong>
                                </td>
                                <td style="padding: 12px;">
                                    <small style="color: #666;">{{ $chamada->mailing->nome ?? 'N/A' }}</small>
                                </td>
                                <td style="padding: 12px;">
                                    <code
                                        style="background-color: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                        {{ $chamada->telefone }}
                                    </code>
                                </td>
                                <td style="padding: 12px; font-size: 13px;">
                                    {{ $chamada->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td style="padding: 12px;">
                                    @if ($chamada->duracao_segundos)
                                        {{ $chamada->duracao_segundos }}s
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="padding: 12px; font-size: 13px;">
                                    {{ $chamada->resultado ?? '-' }}
                                </td>
                                <td style="padding: 12px;">
                                    <span
                                        style="
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: bold;
                                @if ($chamada->status == 'completed') background-color: #d1fae5;
                                    color: #065f46;
                                @elseif($chamada->status == 'failed')
                                    background-color: #fee2e2;
                                    color: #7f1d1d;
                                @else
                                    background-color: #fef3c7;
                                    color: #92400e; @endif
                            ">
                                        {{ ucfirst($chamada->status) }}
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ route('chamadas.detalhes', $chamada->id) }}" class="notion-btn-icon"
                                        title="Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 30px; text-align: center; color: #999;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                    Nenhuma chamada registrada
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if (isset($chamadas) && $chamadas->hasPages())
            <div style="margin-top: 20px; text-align: center;">
                {{ $chamadas->links() }}
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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

        .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
    </style>
@endsection
