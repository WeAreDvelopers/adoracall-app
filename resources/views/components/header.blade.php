<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 flex-shrink-0 z-20">
    <!-- Left: Mobile menu + Brand + Breadcrumb -->
    <div class="flex items-center gap-3">
        <button onclick="openSidebar()" class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors" aria-label="Menu">
            <i class="fas fa-bars text-lg"></i>
        </button>
        <div class="lg:hidden">
            <span class="text-sm font-semibold text-slate-900">URA Dvelopers</span>
        </div>
        <!-- Breadcrumb (preenchido via JS por cada pagina) -->
        <div id="breadcrumbContainer" class="hidden lg:flex items-center gap-2 text-sm text-slate-400"></div>
    </div>

    <!-- Right: Time + User Menu -->
    <div class="flex items-center gap-4">
        <span id="timeDisplay" class="text-xs text-slate-400 font-medium tabular-nums hidden sm:block">{{ date('H:i') }}</span>

        <div class="relative" id="userMenuContainer">
            <div id="headerActions">
                <span class="flex items-center gap-2 text-sm text-slate-400">
                    <i class="fas fa-user-circle"></i> Carregando...
                </span>
            </div>
        </div>
    </div>
</header>

<script>
    function getUserInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0][0].toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function updateTime() {
        const timeDisplay = document.getElementById('timeDisplay');
        if (timeDisplay) {
            const now = new Date();
            timeDisplay.textContent = now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0');
        }
    }

    function initializeHeader() {
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        const empresa = JSON.parse(localStorage.getItem('empresa') || 'null');
        const headerActionsDiv = document.getElementById('headerActions');

        if (user) {
            const initials = getUserInitials(user.name || 'Usu\u00e1rio');
            const userRole = user.role || 'Usu\u00e1rio';
            const empresaNome = empresa ? empresa.nome : '';
            const empresaBadge = empresaNome ? `<span class="hidden md:flex items-center gap-1.5 text-[11px] text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full"><i class="fas fa-building text-[9px]"></i>${empresaNome}</span>` : '';
            const html = `
                <div class="flex items-center gap-3">
                    ${empresaBadge}
                    <button onclick="toggleUserDropdown(event)" class="flex items-center gap-2.5 py-1.5 px-2 rounded-lg hover:bg-slate-50 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0">${initials}</div>
                        <span class="text-sm font-medium text-slate-700 hidden sm:block">${user.name || 'Usu\u00e1rio'}</span>
                        <i class="fas fa-chevron-down text-slate-400 text-[10px] hidden sm:block"></i>
                    </button>
                </div>
                <div id="userDropdown" class="absolute right-0 top-full mt-2 bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 w-64 z-50 hidden">
                    <div class="p-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center text-sm font-semibold">${initials}</div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 truncate">${user.name || 'Usu\u00e1rio'}</p>
                                <p class="text-xs text-slate-500 truncate">${userRole}</p>
                                ${empresaNome ? `<p class="text-xs text-slate-400 truncate"><i class="fas fa-building mr-1"></i>${empresaNome}</p>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="p-1.5">
                        <a href="/profile" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg transition-colors">
                            <i class="fas fa-user w-4 text-center"></i>
                            <span>Meu Perfil</span>
                        </a>
                        <button onclick="logout()" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i>
                            <span>Sair</span>
                        </button>
                    </div>
                </div>
            `;
            headerActionsDiv.innerHTML = html;
        } else {
            headerActionsDiv.innerHTML = `
                <a href="/login" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            `;
        }
    }

    function toggleUserDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    }

    function goToProfile() {
        window.location.href = '/profile';
    }

    async function logout() {
        const token = localStorage.getItem('api_token');

        try {
            await fetch(`${API_BASE_URL}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });
        } catch (error) {
            console.error('Erro ao fazer logout:', error);
        }

        localStorage.removeItem('api_token');
        localStorage.removeItem('user');
        localStorage.removeItem('permissions');
        localStorage.removeItem('empresa');
        window.location.href = '/login';
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeHeader();
        updateTime();

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown && !event.target.closest('#userMenuContainer')) {
                dropdown.classList.add('hidden');
            }
        });
    });

    setInterval(updateTime, 60000);
</script>
