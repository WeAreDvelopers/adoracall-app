@extends('layouts.auth')

@section('title', 'Login - URA Dvelopers')

@section('content')
<div class="w-full max-w-md">
    <!-- Logo & Branding -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-500 rounded-2xl mb-4 shadow-lg shadow-brand-500/20">
            <i class="fas fa-phone-alt text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">URA Dvelopers</h1>
        <p class="text-slate-500 text-sm mt-1">Plataforma de Cobran&ccedil;a Inteligente</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Entrar na sua conta</h2>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <span id="errorText"></span>
        </div>

        <form id="loginForm" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-slate-400 text-sm"></i>
                    </div>
                    <input type="email" id="email" name="email" required autofocus
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                        placeholder="seu@email.com">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Senha</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-slate-400 text-sm"></i>
                    </div>
                    <input type="password" id="password" name="password" required
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                        placeholder="Sua senha">
                </div>
            </div>

            <button type="submit" id="btnLogin"
                class="w-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-2.5 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md hover:shadow-brand-500/20 flex items-center justify-center gap-2 text-sm">
                <i class="fas fa-sign-in-alt"></i>
                <span>Entrar</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <p class="text-sm text-slate-500">
                N&atilde;o tem conta?
                <a href="{{ route('auth.register') }}" class="text-brand-600 hover:text-brand-700 font-medium transition-colors">
                    Registre-se
                </a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <!-- <p class="text-center text-xs text-slate-400 mt-8">
        &copy; {{ date('Y') }} We Are Dvelopers &middot; URA v1.0.0
    </p> -->
</div>
@endsection

@push('scripts')
<script>
// Se já está logado, redirecionar para home
(function() {
    const token = localStorage.getItem('api_token');
    if (token) {
        window.location.href = '/';
        return;
    }
})();

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btnLogin = document.getElementById('btnLogin');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    btnLogin.disabled = true;
    btnLogin.classList.add('opacity-75', 'cursor-not-allowed');
    btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Entrando...</span>';
    errorMessage.classList.add('hidden');

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            const errMsg = typeof data.error === 'string' ? data.error : (data.message || 'Erro ao fazer login');
            throw new Error(errMsg);
        }

        const token = data.data?.token;
        const user = data.data?.user;
        const permissions = data.data?.permissions;

        if (!token || !user) {
            throw new Error('Resposta inv\u00e1lida do servidor');
        }

        localStorage.setItem('api_token', token);
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('permissions', JSON.stringify(permissions || []));

        // Salvar dados da empresa
        const empresa = data.data?.empresa;
        if (empresa) {
            localStorage.setItem('empresa', JSON.stringify(empresa));
        }

        window.location.href = '/';

    } catch (error) {
        errorText.textContent = error.message;
        errorMessage.classList.remove('hidden');
        btnLogin.disabled = false;
        btnLogin.classList.remove('opacity-75', 'cursor-not-allowed');
        btnLogin.innerHTML = '<i class="fas fa-sign-in-alt"></i> <span>Entrar</span>';
    }
});
</script>
@endpush
