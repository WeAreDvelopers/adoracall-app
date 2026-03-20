<aside id="appSidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto transition-transform duration-300 ease-in-out">

    <!-- Logo -->
    <div class="h-16 flex items-center gap-2.5 px-5 border-b border-slate-200 flex-shrink-0">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-phone-alt text-white text-sm"></i>
        </div>
        <div>
            <span class="text-sm font-bold text-slate-900">URA Dvelopers</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar py-4 px-3 space-y-1">

        <!-- Home -->
        <a href="{{ route('home') }}" class="sidebar-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $Request->is('/') ? 'active bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
            <i class="fas fa-home w-4 text-center text-[13px]"></i>
            <span>Home</span>
        </a>

        <!-- Campanhas Section -->
        <div class="sidebar-section pt-3" data-section="campanhas">
            <div class="sidebar-section-header flex items-center gap-2 px-3 py-1.5 cursor-pointer rounded-md hover:bg-slate-50 transition-colors" onclick="toggleSidebarSection(this)" data-expanded="true">
                <i class="fas fa-chevron-right sidebar-section-chevron text-[10px] text-slate-400 transition-transform duration-200"></i>
                <span class="sidebar-section-label text-[11px] font-semibold uppercase tracking-wider text-slate-400">Campanhas</span>
            </div>

            <div class="sidebar-section-content overflow-hidden transition-all duration-300" data-section="campanhas" style="max-height: 1000px; opacity: 1;">
                <a href="/campanhas" class="sidebar-item sidebar-submenu-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('campanhas') || $Request->is('campanha/status-importacoes') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-bullhorn w-4 text-center text-[13px]"></i>
                    <span>Todas as Campanhas</span>
                </a>

                <a href="{{ route('campanha.criar') }}" class="sidebar-item sidebar-submenu-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('campanha/criar') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-plus-circle w-4 text-center text-[13px]"></i>
                    <span>Nova Campanha</span>
                </a>
            </div>
        </div>

        <!-- Operacao Section -->
        <div class="sidebar-section pt-3" data-section="operacao">
            <div class="sidebar-section-header flex items-center gap-2 px-3 py-1.5 cursor-pointer rounded-md hover:bg-slate-50 transition-colors" onclick="toggleSidebarSection(this)" data-expanded="true">
                <i class="fas fa-chevron-right sidebar-section-chevron text-[10px] text-slate-400 transition-transform duration-200"></i>
                <span class="sidebar-section-label text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operação</span>
            </div>

            <div class="sidebar-section-content overflow-hidden transition-all duration-300" data-section="operacao" style="max-height: 1000px; opacity: 1;">
                <a href="/operacao/painel" class="sidebar-item sidebar-submenu-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('operacao/painel') || $Request->is('campanha/gerenciar-fila') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-sliders-h w-4 text-center text-[13px]"></i>
                    <span>Painel de Controle</span>
                </a>

                <a href="/operacao/chamadas" class="sidebar-item sidebar-submenu-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('operacao/chamadas') || $Request->is('campanha/status-ligacoes') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-headset w-4 text-center text-[13px]"></i>
                    <span>Chamadas ao Vivo</span>
                </a>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="sidebar-section pt-3" data-section="analytics">
            <div class="sidebar-section-header flex items-center gap-2 px-3 py-1.5 cursor-pointer rounded-md hover:bg-slate-50 transition-colors" onclick="toggleSidebarSection(this)" data-expanded="true">
                <i class="fas fa-chevron-right sidebar-section-chevron text-[10px] text-slate-400 transition-transform duration-200"></i>
                <span class="sidebar-section-label text-[11px] font-semibold uppercase tracking-wider text-slate-400">Analytics</span>
            </div>

            <div class="sidebar-section-content overflow-hidden transition-all duration-300" data-section="analytics" style="max-height: 1000px; opacity: 1;">
                <a href="{{ route('dashboard.index') }}" class="sidebar-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('dashboard') || $Request->is('dashboard/home') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-chart-line w-4 text-center text-[13px]"></i>
                    <span>Dashboard Geral</span>
                </a>

                <a href="/dashboard/acordos" class="sidebar-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('dashboard/acordos') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-chart-pie w-4 text-center text-[13px]"></i>
                    <span>Acordos &amp; Conversão</span>
                </a>
            </div>
        </div>

        <!-- Configurações (admin e super_admin) -->
        <div class="sidebar-section pt-3" id="sidebarConfigSection" style="display: none;">
            <a href="/configuracoes" class="sidebar-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $Request->is('configuracoes*') ? 'active bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fas fa-cog w-4 text-center text-[13px]"></i>
                <span>Configurações</span>
            </a>
        </div>

        <!-- Admin Section (Super Admin only) -->
        <div class="sidebar-section pt-3" data-section="admin" id="sidebarAdminSection" style="display: none;">
            <div class="sidebar-section-header flex items-center gap-2 px-3 py-1.5 cursor-pointer rounded-md hover:bg-slate-50 transition-colors" onclick="toggleSidebarSection(this)" data-expanded="true">
                <i class="fas fa-chevron-right sidebar-section-chevron text-[10px] text-slate-400 transition-transform duration-200"></i>
                <span class="sidebar-section-label text-[11px] font-semibold uppercase tracking-wider text-red-400">Administração</span>
            </div>

            <div class="sidebar-section-content overflow-hidden transition-all duration-300" data-section="admin" style="max-height: 1000px; opacity: 1;">
                <a href="/admin/empresas" class="sidebar-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('admin/empresas*') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-building w-4 text-center text-[13px]"></i>
                    <span>Empresas</span>
                </a>

                <a href="/admin/usuarios" class="sidebar-item flex items-center gap-3 px-3 pl-8 py-2 rounded-lg text-sm transition-colors {{ $Request->is('admin/usuarios*') ? 'active bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="fas fa-users-cog w-4 text-center text-[13px]"></i>
                    <span>Usuários</span>
                </a>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user && (user.role === 'admin' || user.role === 'super_admin')) {
                const configSection = document.getElementById('sidebarConfigSection');
                if (configSection) configSection.style.display = '';
            }
            if (user && user.role === 'super_admin') {
                const adminSection = document.getElementById('sidebarAdminSection');
                if (adminSection) adminSection.style.display = '';
            }
        });
    </script>

    <!-- Sidebar Footer -->
    <div class="border-t border-slate-200 p-3 flex-shrink-0">
        <button onclick="if(typeof logout === 'function') logout();" class="sidebar-item w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors">
            <i class="fas fa-sign-out-alt w-4 text-center text-[13px]"></i>
            <span>Sair</span>
        </button>
    </div>

    <style>
        /* Active state for sidebar items (preservado para sidebar.js) */
        .sidebar-item.active {
            position: relative;
        }
        .sidebar-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 3px;
            background-color: #f59e0b;
            border-radius: 0 3px 3px 0;
        }
    </style>
</aside>
