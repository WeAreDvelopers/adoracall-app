@extends('layouts.app')

@section('title', $contato->nome_completo . ' - Detalhes')

@section('content')
<div class="container-fluid" style="padding: 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <a href="{{ route('contatos.index') }}" style="color: #6b7280; text-decoration: none; font-size: 20px;" title="Voltar">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 style="margin: 0; color: #1f2937; font-size: 24px;">
                    <i class="fas fa-user"></i> {{ $contato->nome_completo }}
                </h2>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                    <code style="background-color: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 13px;">{{ $contato->telefone }}</code>
                    <span style="
                        padding: 3px 10px;
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
                    ">{{ ucfirst($contato->status ?? 'N/A') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Info + Call Stats -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">

        <!-- Contact Details Card -->
        <div class="notion-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; color: #374151;">
                <i class="fas fa-id-card" style="color: #6b7280;"></i> Dados do Contato
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">CPF</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->cpf_primeiros_digitos ? $contato->cpf_primeiros_digitos . '...' : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Data Nascimento</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->data_nascimento ? $contato->data_nascimento->format('d/m/Y') : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Empresa Credora</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->empresa_credora ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Campanha</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->mailing->nome ?? 'N/A' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Valor Débito</div>
                    <div style="font-size: 14px; color: #1f2937; font-weight: 600;">
                        @if($contato->valor_debito)
                            R$ {{ number_format($contato->valor_debito, 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Vencimento</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->vencimento ? $contato->vencimento->format('d/m/Y') : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Resultado</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ ucfirst($contato->resultado ?? '-') }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Tentativas</div>
                    <div style="font-size: 14px; color: #1f2937;">{{ $contato->tentativas ?? 0 }}</div>
                </div>
            </div>
        </div>

        <!-- Call Stats Card -->
        <div class="notion-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; color: #374151;">
                <i class="fas fa-phone-alt" style="color: #6b7280;"></i> Resumo das Ligações
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="text-align: center; padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: 700; color: #1f2937;">{{ $ligacoesStats['total'] }}</div>
                    <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Total</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: 700; color: #065f46;">{{ $ligacoesStats['atendidas'] }}</div>
                    <div style="font-size: 12px; color: #065f46; text-transform: uppercase; font-weight: 600;">Atendidas</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #eff6ff; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: 700; color: #1e40af;">
                        @if($ligacoesStats['duracao_total'] >= 60)
                            {{ floor($ligacoesStats['duracao_total'] / 60) }}m {{ $ligacoesStats['duracao_total'] % 60 }}s
                        @else
                            {{ $ligacoesStats['duracao_total'] }}s
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #1e40af; text-transform: uppercase; font-weight: 600;">Duração Total</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #fefce8; border-radius: 8px;">
                    <div style="font-size: 32px; font-weight: 700; color: #92400e;">
                        @if($ligacoesStats['duracao_media'] >= 60)
                            {{ floor($ligacoesStats['duracao_media'] / 60) }}m {{ $ligacoesStats['duracao_media'] % 60 }}s
                        @else
                            {{ $ligacoesStats['duracao_media'] }}s
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #92400e; text-transform: uppercase; font-weight: 600;">Duração Média</div>
                </div>
            </div>

            @if($contato->propostas->count() > 0)
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Propostas de Pagamento</div>
                <div style="font-size: 24px; font-weight: 700; color: #7c3aed;">{{ $contato->propostas->count() }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Calls History -->
    <div class="notion-card" style="margin-bottom: 24px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0; font-size: 16px; color: #374151;">
                <i class="fas fa-history" style="color: #6b7280;"></i> Histórico de Ligações
            </h3>
        </div>

        @if($ligacoes->count() > 0)
        <div style="overflow-x: auto;">
            <table class="notion-table" style="width: 100%;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Data/Hora</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Duração</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Status</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Resultado</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Atendida</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Validação</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Gravação</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ligacoes as $ligacao)
                    <tr style="border-bottom: 1px solid #f0f0f0;" id="row-{{ $ligacao->id }}">
                        <td style="padding: 12px; font-size: 13px; color: #374151;">
                            {{ $ligacao->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td style="padding: 12px; text-align: center; font-size: 13px; color: #374151; font-weight: 600;">
                            @if($ligacao->duracao)
                                @if($ligacao->duracao >= 60)
                                    {{ floor($ligacao->duracao / 60) }}m {{ $ligacao->duracao % 60 }}s
                                @else
                                    {{ $ligacao->duracao }}s
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="
                                padding: 3px 10px;
                                border-radius: 9999px;
                                font-size: 11px;
                                font-weight: 600;
                                @if(in_array($ligacao->status, ['completed', 'finished']))
                                    background-color: #d1fae5; color: #065f46;
                                @elseif(in_array($ligacao->status, ['initiated', 'ongoing', 'processing', 'in_progress']))
                                    background-color: #dbeafe; color: #1e40af;
                                @elseif(in_array($ligacao->status, ['failed']))
                                    background-color: #fee2e2; color: #991b1b;
                                @else
                                    background-color: #f3f4f6; color: #374151;
                                @endif
                            ">{{ $ligacao->status ?? 'N/A' }}</span>
                        </td>
                        <td style="padding: 12px; text-align: center; font-size: 13px; color: #6b7280;">
                            {{ ucfirst($ligacao->resultado ?? '-') }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($ligacao->foi_atendida)
                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 16px;" title="Atendida"></i>
                            @else
                                <i class="fas fa-times-circle" style="color: #ef4444; font-size: 16px;" title="Não atendida"></i>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($ligacao->validacao_sucesso === true)
                                <span style="padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; background-color: #d1fae5; color: #065f46;">
                                    <i class="fas fa-shield-alt"></i> OK
                                </span>
                            @elseif($ligacao->validacao_sucesso === false)
                                <span style="padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; background-color: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-shield-alt"></i> Falhou
                                </span>
                            @else
                                <span style="color: #d1d5db;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($ligacao->url_gravacao)
                                <a href="{{ $ligacao->url_gravacao }}" target="_blank" class="notion-btn-icon" title="Ouvir gravação" style="color: #7c3aed;">
                                    <i class="fas fa-play-circle"></i>
                                </a>
                            @else
                                <span style="color: #d1d5db;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @php
                                $hasDetails = $ligacao->detalhes || $ligacao->callHistory || $ligacao->cpf_informado;
                            @endphp
                            @if($hasDetails)
                                <button onclick="toggleDetails({{ $ligacao->id }})" class="notion-btn-icon" title="Ver detalhes">
                                    <i class="fas fa-chevron-down" id="icon-{{ $ligacao->id }}"></i>
                                </button>
                            @else
                                <span style="color: #d1d5db;">-</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Expandable details row --}}
                    @if($hasDetails ?? false)
                    <tr id="details-{{ $ligacao->id }}" style="display: none; background-color: #f9fafb;">
                        <td colspan="8" style="padding: 0;">
                            <div style="padding: 16px 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">

                                    {{-- Identificadores --}}
                                    @if($ligacao->sid_twilio || $ligacao->call_id_retell)
                                    <div>
                                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">IDs da Chamada</div>
                                        @if($ligacao->sid_twilio)
                                            <div style="font-size: 12px; color: #6b7280;">Twilio: <code style="background: #e5e7eb; padding: 1px 4px; border-radius: 2px;">{{ $ligacao->sid_twilio }}</code></div>
                                        @endif
                                        @if($ligacao->call_id_retell)
                                            <div style="font-size: 12px; color: #6b7280;">Retell: <code style="background: #e5e7eb; padding: 1px 4px; border-radius: 2px;">{{ $ligacao->call_id_retell }}</code></div>
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Validação de segurança --}}
                                    @if($ligacao->cpf_informado || $ligacao->data_nascimento_informada)
                                    <div>
                                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Validação de Segurança</div>
                                        @if($ligacao->cpf_informado)
                                            <div style="font-size: 12px; color: #6b7280;">CPF informado: <strong>{{ $ligacao->cpf_informado }}...</strong></div>
                                        @endif
                                        @if($ligacao->data_nascimento_informada)
                                            <div style="font-size: 12px; color: #6b7280;">Nasc. informado: <strong>{{ $ligacao->data_nascimento_informada->format('d/m/Y') }}</strong></div>
                                        @endif
                                        <div style="font-size: 12px; color: #6b7280;">Tentativas: <strong>{{ $ligacao->tentativas_validacao }}</strong></div>
                                    </div>
                                    @endif

                                    {{-- CallHistory extra data --}}
                                    @if($ligacao->callHistory)
                                    <div>
                                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Análise da Chamada</div>
                                        @if($ligacao->callHistory->user_sentiment)
                                            <div style="font-size: 12px; color: #6b7280;">Sentimento: <strong>{{ ucfirst($ligacao->callHistory->user_sentiment) }}</strong></div>
                                        @endif
                                        @if($ligacao->callHistory->call_successful !== null)
                                            <div style="font-size: 12px; color: #6b7280;">Sucesso:
                                                @if($ligacao->callHistory->call_successful)
                                                    <i class="fas fa-check" style="color: #10b981;"></i>
                                                @else
                                                    <i class="fas fa-times" style="color: #ef4444;"></i>
                                                @endif
                                            </div>
                                        @endif
                                        @if($ligacao->callHistory->cost_formatted)
                                            <div style="font-size: 12px; color: #6b7280;">Custo: <strong>{{ $ligacao->callHistory->cost_formatted }}</strong></div>
                                        @endif
                                    </div>
                                    @endif
                                </div>

                                {{-- Resumo da chamada --}}
                                @if($ligacao->callHistory && $ligacao->callHistory->call_summary)
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">Resumo da Chamada</div>
                                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; font-size: 13px; color: #374151; line-height: 1.6;">
                                        {{ $ligacao->callHistory->call_summary }}
                                    </div>
                                </div>
                                @endif

                                {{-- Transcrição --}}
                                @php
                                    $transcript = null;
                                    if ($ligacao->callHistory && $ligacao->callHistory->transcript) {
                                        $transcript = $ligacao->callHistory->transcript;
                                    } elseif (is_array($ligacao->detalhes) && isset($ligacao->detalhes['transcription'])) {
                                        $transcript = $ligacao->detalhes['transcription'];
                                    }
                                @endphp
                                @if($transcript)
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">Transcrição</div>
                                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; font-size: 13px; color: #374151; line-height: 1.6; max-height: 300px; overflow-y: auto;">
                                        {!! nl2br(e(is_string($transcript) ? $transcript : json_encode($transcript, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) !!}
                                    </div>
                                </div>
                                @endif

                                {{-- Detalhes JSON extras --}}
                                @if(is_array($ligacao->detalhes) && count($ligacao->detalhes) > 0)
                                @php
                                    $extras = collect($ligacao->detalhes)->except(['transcription']);
                                @endphp
                                @if($extras->count() > 0)
                                <div>
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">Dados Adicionais</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        @foreach($extras as $key => $value)
                                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px;">
                                            <span style="font-size: 11px; color: #9ca3af; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                            <div style="font-size: 13px; color: #374151; font-weight: 500;">
                                                {{ is_string($value) ? $value : json_encode($value) }}
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                @endif

                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="padding: 40px; text-align: center; color: #9ca3af;">
            <i class="fas fa-phone-slash" style="font-size: 36px; margin-bottom: 12px; display: block;"></i>
            Nenhuma ligação realizada para este contato
        </div>
        @endif
    </div>

    {{-- Propostas de Pagamento --}}
    @if($contato->propostas->count() > 0)
    <div class="notion-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0; font-size: 16px; color: #374151;">
                <i class="fas fa-file-invoice-dollar" style="color: #6b7280;"></i> Propostas de Pagamento
            </h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="notion-table" style="width: 100%;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e0e0e0;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Data</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Tipo</th>
                        <th style="padding: 12px; text-align: right; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Valor Original</th>
                        <th style="padding: 12px; text-align: right; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Valor Proposta</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Desconto</th>
                        <th style="padding: 12px; text-align: center; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contato->propostas->sortByDesc('created_at') as $proposta)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 12px; font-size: 13px; color: #374151;">{{ $proposta->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding: 12px; font-size: 13px; color: #6b7280;">{{ ucfirst($proposta->tipo_proposta ?? '-') }}</td>
                        <td style="padding: 12px; text-align: right; font-size: 13px; color: #374151;">
                            R$ {{ number_format($proposta->valor_original ?? 0, 2, ',', '.') }}
                        </td>
                        <td style="padding: 12px; text-align: right; font-size: 13px; color: #374151; font-weight: 600;">
                            R$ {{ number_format($proposta->valor_proposta ?? 0, 2, ',', '.') }}
                        </td>
                        <td style="padding: 12px; text-align: center; font-size: 13px; color: #10b981; font-weight: 600;">
                            @if($proposta->desconto)
                                {{ number_format($proposta->desconto, 1) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="
                                padding: 3px 10px;
                                border-radius: 9999px;
                                font-size: 11px;
                                font-weight: 600;
                                @if($proposta->status == 'aceita' || $proposta->status == 'pago')
                                    background-color: #d1fae5; color: #065f46;
                                @elseif($proposta->status == 'pendente' || $proposta->status == 'enviada')
                                    background-color: #fef3c7; color: #92400e;
                                @elseif($proposta->status == 'recusada' || $proposta->status == 'expirada')
                                    background-color: #fee2e2; color: #991b1b;
                                @else
                                    background-color: #f3f4f6; color: #374151;
                                @endif
                            ">{{ ucfirst($proposta->status ?? 'N/A') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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

h2, h3 {
    margin: 0;
    color: #1f2937;
}
</style>

<script>
function toggleDetails(id) {
    const row = document.getElementById('details-' + id);
    const icon = document.getElementById('icon-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        row.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>
@endsection
