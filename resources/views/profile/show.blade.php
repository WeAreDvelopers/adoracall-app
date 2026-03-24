@extends('layouts.app')

@section('title', 'Perfil do Usuário - AdoraCall')

@section('content')
    <div class="notion-card">
        <div class="notion-card-header">
            <h2><i class="fas fa-user-circle"></i> Meu Perfil</h2>
            <a href="{{ route('profile.edit') }}" class="notion-btn notion-btn-primary">
                <i class="fas fa-edit"></i> Editar Perfil
            </a>
        </div>

        <div class="profile-container" style="padding: 30px;">
            <!-- Avatar e Informações Básicas -->
            <div class="profile-header" style="display: flex; align-items: center; margin-bottom: 40px; gap: 30px;">
                <div class="profile-avatar" style="flex-shrink: 0;">
                    <div style="
                        width: 120px;
                        height: 120px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 48px;
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                    ">
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="profile-basic-info" style="flex: 1;">
                    <h3 style="margin: 0 0 10px 0; font-size: 28px; color: #333;">
                        {{ $user->name }}
                    </h3>
                    <p style="margin: 5px 0; color: #666; font-size: 16px;">
                        <strong>Email:</strong> {{ $user->email }}
                    </p>
                    <p style="margin: 5px 0; color: #666; font-size: 16px;">
                        <strong>Role:</strong>
                        <span style="
                            display: inline-block;
                            padding: 4px 12px;
                            background: #667eea;
                            color: white;
                            border-radius: 20px;
                            font-size: 14px;
                            text-transform: capitalize;
                        ">
                            @if($user->role === 'admin')
                                <i class="fas fa-crown"></i> Administrador
                            @elseif($user->role === 'supervisor')
                                <i class="fas fa-user-tie"></i> Supervisor
                            @elseif($user->role === 'operador')
                                <i class="fas fa-headset"></i> Operador
                            @else
                                {{ $user->role }}
                            @endif
                        </span>
                    </p>
                    <p style="margin: 5px 0; color: #666; font-size: 16px;">
                        <strong>Status:</strong>
                        <span style="
                            display: inline-block;
                            padding: 4px 12px;
                            background: {{ $user->active ? '#28a745' : '#dc3545' }};
                            color: white;
                            border-radius: 20px;
                            font-size: 14px;
                        ">
                            <i class="fas fa-{{ $user->active ? 'check-circle' : 'times-circle' }}"></i>
                            {{ $user->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Seção de Datas -->
            <div class="profile-dates" style="
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            ">
                <h4 style="margin: 0 0 15px 0; color: #333;">Informações de Registro</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;">Data de Criação</p>
                        <p style="margin: 0; color: #333; font-weight: 500;">
                            {{ $user->created_at->format('d/m/Y \à\s H:i') }}
                        </p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;">Última Atualização</p>
                        <p style="margin: 0; color: #333; font-weight: 500;">
                            {{ $user->updated_at->format('d/m/Y \à\s H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Seção de Permissões -->
            <div class="profile-permissions" style="margin-top: 30px;">
                <h4 style="margin: 0 0 20px 0; color: #333;">Minhas Permissões</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                    @php
                        $permissions = $user->getRolePermissions();
                    @endphp

                    @if(empty($permissions))
                        <p style="color: #999; grid-column: 1 / -1;">Nenhuma permissão atribuída</p>
                    @else
                        @foreach($permissions as $permission)
                            <div style="
                                padding: 12px 15px;
                                background: #e8f0fe;
                                border-left: 4px solid #667eea;
                                border-radius: 4px;
                                font-size: 14px;
                                color: #333;
                            ">
                                <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                                {{ $permission }}
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Seção de Ações -->
            <div class="profile-actions" style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
                <h4 style="margin: 0 0 20px 0; color: #333;">Ações Rápidas</h4>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('profile.edit') }}" class="notion-btn notion-btn-primary">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                    <form action="{{ route('auth.logout') }}" method="POST" style="display: inline;">
                        {{ csrf_field() }}
                        <button type="submit" class="notion-btn notion-btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-container {
            max-width: 900px;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-basic-info {
                text-align: center;
            }

            .profile-dates {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('js/profile.js') }}"></script>
@endsection
