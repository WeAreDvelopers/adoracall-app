@extends('layouts.app')

@section('title', 'Admin - Nova Empresa')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="/admin/empresas" class="hover:text-brand-600 transition-colors">Empresas</a>
            <i class="fas fa-chevron-right text-xs text-slate-400"></i>
            <span class="text-slate-700">Nova Empresa</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">
            <i class="fas fa-plus-circle text-brand-500 mr-2"></i>Nova Empresa
        </h1>
        <p class="text-slate-500 text-sm mt-1">Cadastre uma nova empresa no sistema</p>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="mb-4"></div>

    <!-- Form -->
    <form id="criarEmpresaForm" class="space-y-6">

        <!-- Informacoes Basicas -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">
                <i class="fas fa-info-circle text-brand-500 mr-2"></i>Informacoes da Empresa
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="nome">
                        Nome da Empresa <span class="text-red-500">*</span>
                    </label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           type="text" id="nome" name="nome" placeholder="Ex: Empresa ABC Ltda" required>
                    <p class="text-xs text-slate-400 mt-1">Nome identificador da empresa</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="cnpj">
                        CNPJ
                    </label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18"
                           oninput="formatCnpjInput(this)">
                    <p class="text-xs text-slate-400 mt-1">Opcional - CNPJ da empresa</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">
                        Email
                    </label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           type="email" id="email" name="email" placeholder="contato@empresa.com.br">
                    <p class="text-xs text-slate-400 mt-1">Opcional - Email principal de contato</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="telefone">
                        Telefone
                    </label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" maxlength="15"
                           oninput="formatPhoneInput(this)">
                    <p class="text-xs text-slate-400 mt-1">Opcional - Telefone de contato</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="max_usuarios">
                        Max. Usuarios
                    </label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                           type="number" id="max_usuarios" name="max_usuarios" value="10" min="1" max="1000">
                    <p class="text-xs text-slate-400 mt-1">Limite de usuarios para esta empresa</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="endereco">
                        Endereco
                    </label>
                    <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                              id="endereco" name="endereco" rows="3" placeholder="Rua, numero, bairro, cidade - UF, CEP"></textarea>
                    <p class="text-xs text-slate-400 mt-1">Opcional - Endereco completo da empresa</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 justify-end">
            <a href="/admin/empresas" class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Cancelar
            </a>
            <button type="submit" id="btnSubmit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg text-sm transition-colors shadow-sm">
                <i class="fas fa-check-circle mr-1"></i> Criar Empresa
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function formatCnpjInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 14) value = value.substring(0, 14);
        if (value.length > 12) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
        } else if (value.length > 8) {
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
        } else if (value.length > 5) {
            value = value.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
        } else if (value.length > 2) {
            value = value.replace(/(\d{2})(\d{0,3})/, '$1.$2');
        }
        input.value = value;
    }

    function formatPhoneInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        if (value.length > 6) {
            value = value.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        }
        input.value = value;
    }

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const bgClass = type === 'success'
            ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
            : 'bg-red-50 border-red-200 text-red-700';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        alertContainer.innerHTML = `
            <div class="p-3 ${bgClass} border rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('criarEmpresaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitForm();
        });
    });

    async function submitForm() {
        const btnSubmit = document.getElementById('btnSubmit');

        try {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Criando...';

            const formData = {
                nome: document.getElementById('nome').value.trim(),
                cnpj: document.getElementById('cnpj').value.replace(/\D/g, '') || null,
                email: document.getElementById('email').value.trim() || null,
                telefone: document.getElementById('telefone').value.replace(/\D/g, '') || null,
                endereco: document.getElementById('endereco').value.trim() || null,
                max_usuarios: parseInt(document.getElementById('max_usuarios').value) || 10,
            };

            if (!formData.nome) {
                showAlert('O nome da empresa e obrigatorio.', 'error');
                resetButton();
                return;
            }

            const response = await fetchWithAuth(`${API_BASE_URL}/admin/empresas`, {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                showNotification('Empresa criada com sucesso!', 'success');
                showAlert('Empresa criada com sucesso! Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = '/admin/empresas';
                }, 1500);
            } else {
                let errorMsg = data.message || 'Erro ao criar empresa';
                if (data.errors) {
                    const errors = Object.entries(data.errors).map(([field, msgs]) => {
                        return `<strong>${field}:</strong> ${Array.isArray(msgs) ? msgs[0] : msgs}`;
                    });
                    errorMsg = errors.join('<br>');
                }
                showAlert(errorMsg, 'error');
                resetButton();
            }
        } catch (error) {
            console.error('Erro:', error);
            showAlert('Erro ao processar requisicao: ' + error.message, 'error');
            resetButton();
        }
    }

    function resetButton() {
        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Criar Empresa';
    }
</script>
@endpush
