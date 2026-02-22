@extends('layouts.app')

@section('title', 'Home - Sistema de URA')

@section('content')
<div class="notion-text-center notion-mb">
    <h1 style="color: var(--notion-text);">Selecione o Tipo de Chamada</h1>
    <p class="notion-text-secondary" style="font-size: var(--notion-font-size-lg);">
        Escolha entre cobrança ou vendas para iniciar uma nova chamada automatizada
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: var(--notion-space-xl); margin: var(--notion-space-2xl) 0;">
    <!-- Card de Cobrança -->
    <div class="notion-card">
        <div style="text-align: center;">
            <div style="font-size: 64px; margin-bottom: var(--notion-space-lg); color: var(--dvelopers-gold);">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h2 style="color: var(--notion-text); font-size: var(--notion-font-size-xl); margin-bottom: var(--notion-space-md);">
                💰 Cobrança
            </h2>
            <p class="notion-text-secondary" style="margin-bottom: var(--notion-space-lg); font-size: var(--notion-font-size-md);">
                Sistema completo de cobrança automatizada com validação de segurança
            </p>

            <ul style="list-style: none; padding: 0; margin: var(--notion-space-lg) 0; text-align: left; display: inline-block;">
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Validação de CPF e Data de Nascimento
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Cálculo automático de descontos
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Opções de parcelamento
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Histórico de tentativas
                </li>
            </ul>

            <a href="{{ route('pages.cobranca') }}" class="notion-btn notion-btn-primary" style="width: 100%; justify-content: center; margin-top: var(--notion-space-lg);">
                <i class="fas fa-arrow-right"></i> Iniciar Cobrança
            </a>
        </div>
    </div>

    <!-- Card de Vendas -->
    <div class="notion-card">
        <div style="text-align: center;">
            <div style="font-size: 64px; margin-bottom: var(--notion-space-lg); color: var(--dvelopers-gold);">
                <i class="fas fa-handshake"></i>
            </div>
            <h2 style="color: var(--notion-text); font-size: var(--notion-font-size-xl); margin-bottom: var(--notion-space-md);">
                🤝 Vendas
            </h2>
            <p class="notion-text-secondary" style="margin-bottom: var(--notion-space-lg); font-size: var(--notion-font-size-md);">
                Interface simplificada para chamadas de vendas e prospecção
            </p>

            <ul style="list-style: none; padding: 0; margin: var(--notion-space-lg) 0; text-align: left; display: inline-block;">
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Formulário simplificado
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Contato rápido
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Ideal para prospecção
                </li>
                <li style="color: var(--notion-text-secondary); padding: var(--notion-space-sm) 0; padding-left: var(--notion-space-lg); position: relative;">
                    <span style="position: absolute; left: 0; color: var(--dvelopers-gold); font-weight: 700;">✓</span>
                    Foco em conversão
                </li>
            </ul>

            <a href="{{ route('pages.vendas') }}" class="notion-btn notion-btn-primary" style="width: 100%; justify-content: center; margin-top: var(--notion-space-lg);">
                <i class="fas fa-arrow-right"></i> Iniciar Vendas
            </a>
        </div>
    </div>
</div>

<div style="text-align: center; margin-top: var(--notion-space-2xl);">
    <a href="{{ route('dashboard.index') }}" class="notion-btn notion-btn-secondary">
        <i class="fas fa-chart-line"></i> Acessar Dashboard de Métricas
    </a>
</div>
@endsection
