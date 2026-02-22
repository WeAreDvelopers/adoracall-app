@extends('layouts.app')

@section('title', 'Home - Dashboard URA')

@section('content')
<div class="container-fluid" style="padding: 20px;">

    <!-- Stats Cards -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-3">
            <div class="notion-card" style="padding: 20px; text-align: center;">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">Campanhas Ativas</div>
                <div style="font-size: 32px; font-weight: bold; color: #2563eb;">
                    {{ $stats['campanhas_ativas'] ?? 0 }}
                </div>
                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                    total de {{ $stats['total_campanhas'] ?? 0 }}
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="notion-card" style="padding: 20px; text-align: center;">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">Chamadas Hoje</div>
                <div style="font-size: 32px; font-weight: bold; color: #16a34a;">
                    {{ $stats['chamadas_hoje'] ?? 0 }}
                </div>
                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                    últimas 24 horas
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="notion-card" style="padding: 20px; text-align: center;">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">Propostas Pendentes</div>
                <div style="font-size: 32px; font-weight: bold; color: #ea580c;">
                    {{ $stats['propostas_pendentes'] ?? 0 }}
                </div>
                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                    aguardando pagamento
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="notion-card" style="padding: 20px; text-align: center;">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">Taxa Sucesso</div>
                <div style="font-size: 32px; font-weight: bold; color: #7c3aed;">
                    {{ $stats['taxa_sucesso'] ?? 0 }}%
                </div>
                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                    últimos 30 dias
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calls -->
    <div class="notion-card" style="margin-bottom: 30px;">
        <div class="notion-card-header">
            <h3 style="margin: 0;">
                <i class="fas fa-phone"></i> Últimas Chamadas
            </h3>
        </div>
        <div style="padding: 20px; overflow-x: auto;">
            <table class="notion-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 10px; text-align: left; color: #666;">Contato</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Campanha</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Data/Hora</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Status</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Duração</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_calls ?? [] as $call)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 10px;">
                            <strong>{{ $call->contato->nome ?? 'N/A' }}</strong><br>
                            <small style="color: #999;">{{ $call->contato->telefone ?? '' }}</small>
                        </td>
                        <td style="padding: 10px;">{{ $call->mailing->nome ?? 'N/A' }}</td>
                        <td style="padding: 10px;">{{ $call->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td style="padding: 10px;">
                            <span style="
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: bold;
                                background-color:
                                    @if($call->status == 'completed') #d1fae5
                                    @elseif($call->status == 'failed') #fee2e2
                                    @else #fef3c7
                                    @endif;
                                color:
                                    @if($call->status == 'completed') #065f46
                                    @elseif($call->status == 'failed') #7f1d1d
                                    @else #92400e
                                    @endif;
                            ">
                                {{ ucfirst($call->status) }}
                            </span>
                        </td>
                        <td style="padding: 10px;">
                            {{ $call->duracao_segundos ? $call->duracao_segundos . 's' : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #999;">
                            Nenhuma chamada recente
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Proposals -->
    <div class="notion-card">
        <div class="notion-card-header">
            <h3 style="margin: 0;">
                <i class="fas fa-file-invoice"></i> Propostas de Pagamento Pendentes
            </h3>
        </div>
        <div style="padding: 20px; overflow-x: auto;">
            <table class="notion-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 10px; text-align: left; color: #666;">Contato</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Valor</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Parcelas</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Vencimento</th>
                        <th style="padding: 10px; text-align: left; color: #666;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending_proposals ?? [] as $proposal)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 10px;">{{ $proposal->contato->nome ?? 'N/A' }}</td>
                        <td style="padding: 10px; font-weight: bold;">
                            R$ {{ number_format($proposal->valor_final, 2, ',', '.') }}
                        </td>
                        <td style="padding: 10px;">{{ $proposal->parcelas }}x</td>
                        <td style="padding: 10px;">
                            @if($proposal->expires_at)
                                {{ $proposal->expires_at->format('d/m/Y') }}
                                @if($proposal->expires_at->isPast())
                                    <span style="color: #dc2626; font-weight: bold;">(Expirado)</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 10px;">
                            <a href="javascript:void(0)" class="notion-btn-small"
                               onclick="resendSMS({{ $proposal->id }})">
                                <i class="fas fa-sms"></i> Reenviar SMS
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #999;">
                            Nenhuma proposta pendente
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.notion-btn-small {
    padding: 6px 12px;
    background-color: #2563eb;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    cursor: pointer;
    border: none;
    display: inline-block;
}

.notion-btn-small:hover {
    background-color: #1d4ed8;
    text-decoration: none;
    color: white;
}

.notion-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.notion-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.notion-card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.notion-table {
    width: 100%;
    font-size: 14px;
}

.notion-table th {
    background-color: #f9fafb;
    font-weight: 600;
    padding: 10px;
    text-align: left;
    color: #666;
}

.notion-table td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}
</style>

<script>
function resendSMS(proposalId) {
    if (confirm('Reenviar SMS para esta proposta?')) {
        // Implementar depois
        alert('SMS será reenviado em breve');
    }
}
</script>
@endsection
