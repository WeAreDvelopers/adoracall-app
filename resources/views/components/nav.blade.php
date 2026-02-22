<nav class="main-nav" style="background: #f8f9fa; border-bottom: 2px solid #e0e0e0; padding: 0;">
    <ul class="nav-list" style="max-width: 1400px; margin: 0 auto; display: flex; list-style: none; padding: 0;">
        <li style="flex: 1; border-right: 1px solid #e0e0e0;">
            <a href="{{ route('home') }}" class="nav-link {{ $Request->is('/') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                🏠 Home
            </a>
        </li>
        <li style="flex: 1; border-right: 1px solid #e0e0e0;">
            <a href="{{ route('campanha.importacao') }}" class="nav-link {{ $Request->is('campanha/importacao') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                📥 Importar
            </a>
        </li>
        <li style="flex: 1; border-right: 1px solid #e0e0e0;">
            <a href="{{ route('campanha.status-importacoes') }}" class="nav-link {{ $Request->is('campanha/status-importacoes') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                📊 Status Importação
            </a>
        </li>
        <li style="flex: 1; border-right: 1px solid #e0e0e0;">
            <a href="{{ route('campanha.status-fila') }}" class="nav-link {{ $Request->is('campanha/status-fila') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                ⏳ Fila
            </a>
        </li>
        <li style="flex: 1; border-right: 1px solid #e0e0e0;">
            <a href="{{ route('campanha.status-ligacoes') }}" class="nav-link {{ $Request->is('campanha/status-ligacoes') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                📞 Ligações
            </a>
        </li>
        <li style="flex: 1;">
            <a href="{{ route('dashboard.index') }}" class="nav-link {{ $Request->is('dashboard*') ? 'active' : '' }}" style="display: block; padding: 15px 20px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent;">
                📈 Dashboard
            </a>
        </li>
    </ul>
    <style>
        .nav-link.active {
            color: #667eea !important;
            border-bottom-color: #667eea !important;
            background: white !important;
        }
        .nav-link:hover {
            background: #fff !important;
            color: #667eea !important;
        }
    </style>
</nav>
