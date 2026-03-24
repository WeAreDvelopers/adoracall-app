@extends('layouts.app')

@section('title', 'Editar Perfil - AdoraCall')

@section('content')
    <div class="notion-card">
        <div class="notion-card-header">
            <h2><i class="fas fa-edit"></i> Editar Perfil</h2>
            <a href="{{ route('profile.show') }}" class="notion-btn notion-btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" style="padding: 30px;">
            {{ csrf_field() }}
            {{ method_field('PUT') }}

            <!-- Nome -->
            <div style="margin-bottom: 25px;">
                <label style="
                    display: block;
                    margin-bottom: 8px;
                    color: #333;
                    font-weight: 500;
                ">
                    <i class="fas fa-user"></i> Nome Completo
                </label>
                <input type="text"
                    name="name"
                    value="{{ $user->name }}"
                    placeholder="Digite seu nome completo"
                    required
                    style="
                        width: 100%;
                        padding: 12px 15px;
                        border: 2px solid #e0e0e0;
                        border-radius: 8px;
                        font-size: 16px;
                        font-family: inherit;
                        transition: all 0.3s;
                        box-sizing: border-box;
                    "
                    onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'"
                    onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                />
                <small style="color: #999; display: block; margin-top: 5px;">
                    Este é o seu nome que aparecerá em todo o sistema
                </small>
            </div>

            <!-- Email -->
            <div style="margin-bottom: 25px;">
                <label style="
                    display: block;
                    margin-bottom: 8px;
                    color: #333;
                    font-weight: 500;
                ">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email"
                    name="email"
                    value="{{ $user->email }}"
                    placeholder="seu.email@exemplo.com"
                    required
                    style="
                        width: 100%;
                        padding: 12px 15px;
                        border: 2px solid #e0e0e0;
                        border-radius: 8px;
                        font-size: 16px;
                        font-family: inherit;
                        transition: all 0.3s;
                        box-sizing: border-box;
                    "
                    onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'"
                    onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                />
                <small style="color: #999; display: block; margin-top: 5px;">
                    Seu email de login no sistema
                </small>
            </div>

            <!-- Informações Adicionais (somente leitura) -->
            <div style="
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 25px;
            ">
                <h4 style="margin: 0 0 15px 0; color: #333;">Informações do Perfil</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; color: #666; font-weight: 500; margin-bottom: 5px;">
                            <i class="fas fa-crown"></i> Role
                        </label>
                        <input type="text"
                            value="@if($user->role === 'admin')
                                Administrador
                            @elseif($user->role === 'supervisor')
                                Supervisor
                            @elseif($user->role === 'operador')
                                Operador
                            @else
                                {{ $user->role }}
                            @endif"
                            disabled
                            style="
                                width: 100%;
                                padding: 10px 12px;
                                background: #e8f0fe;
                                border: 1px solid #d0d8f2;
                                border-radius: 6px;
                                color: #333;
                                font-size: 14px;
                            "
                        />
                        <small style="color: #999; display: block; margin-top: 5px;">
                            Contate um administrador para alterar
                        </small>
                    </div>

                    <div>
                        <label style="display: block; color: #666; font-weight: 500; margin-bottom: 5px;">
                            <i class="fas fa-{{ $user->active ? 'check-circle' : 'times-circle' }}"></i> Status
                        </label>
                        <input type="text"
                            value="{{ $user->active ? 'Ativo' : 'Inativo' }}"
                            disabled
                            style="
                                width: 100%;
                                padding: 10px 12px;
                                background: #e8f0fe;
                                border: 1px solid #d0d8f2;
                                border-radius: 6px;
                                color: #333;
                                font-size: 14px;
                            "
                        />
                        <small style="color: #999; display: block; margin-top: 5px;">
                            Gerenciado pelo administrador
                        </small>
                    </div>
                </div>

                <div>
                    <label style="display: block; color: #666; font-weight: 500; margin-bottom: 5px;">
                        <i class="fas fa-calendar"></i> Membro Desde
                    </label>
                    <input type="text"
                        value="{{ $user->created_at->format('d/m/Y \à\s H:i') }}"
                        disabled
                        style="
                            width: 100%;
                            padding: 10px 12px;
                            background: #e8f0fe;
                            border: 1px solid #d0d8f2;
                            border-radius: 6px;
                            color: #333;
                            font-size: 14px;
                        "
                    />
                </div>
            </div>

            <!-- Botões de Ação -->
            <div style="display: flex; gap: 10px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <button type="submit" class="notion-btn notion-btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <a href="{{ route('profile.show') }}" class="notion-btn notion-btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <style>
        input:disabled {
            cursor: not-allowed;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }

            div[style*="display: flex"] {
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/profile.js') }}"></script>
@endsection
