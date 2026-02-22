<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() ?? '' }}">
    <title>@yield('title', 'URA Dvelopers')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Notion Design System CSS (legacy - migração gradual) -->
    <link rel="stylesheet" href="{{ asset('css/notion.css') }}">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

    <!-- Overrides para Tailwind prevalecer sobre notion.css -->
    <style>
        /* Reset notion.css conflicts */
        body.notion-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .notion-layout-with-sidebar { display: none; }
        .notion-header { display: none; }
        .notion-footer { display: none; }
        .notion-main-content { margin-left: 0; }

        /* Sidebar toggle animation */
        .sidebar-overlay {
            transition: opacity 0.3s ease;
        }

        /* Smooth page transitions */
        .page-content {
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar personalizada */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    @stack('head-scripts')
    @yield('styles')
</head>
<body class="notion-body font-sans bg-slate-50 text-slate-900">

    <!-- Auth Guard - Redireciona para login se não autenticado -->
    <script>
        (function() {
            const token = localStorage.getItem('api_token');
            if (!token) {
                window.location.href = '/login';
                document.body.style.display = 'none';
                return;
            }
        })();
    </script>

    <!-- App Shell -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black/40 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Header -->
            @include('components.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto custom-scrollbar bg-slate-50">
                <div class="page-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    @yield('content')
                </div>

                @include('components.footer')
            </main>
        </div>
    </div>

    <!-- Base Scripts -->
    <script>
        // Wrapper seguro para localStorage
        const originalLocalStorage = window.localStorage;
        window.safeParseJSON = function(str, defaultValue = null) {
            try {
                if (!str || str === 'undefined' || str === 'null' || typeof str === 'undefined') {
                    return defaultValue;
                }
                return JSON.parse(str);
            } catch (e) {
                console.warn('Erro ao fazer parse JSON:', e, 'String:', str);
                return defaultValue;
            }
        };
    </script>
    <!-- Auth Interceptor - Carrega antes de config.js para interceptar fetch -->
    <script src="{{ asset('js/auth-interceptor.js') }}"></script>
    <script src="{{ asset('js/config.js') }}"></script>
    <script src="{{ asset('js/notifications.js') }}"></script>

    <!-- Bibliotecas -->
    <script src="{{ asset('js/filtros.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/feedback.js') }}"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>

    <script>
        // Mobile sidebar toggle
        function openSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Close sidebar when clicking on a sidebar item (mobile)
            if (window.innerWidth < 1024) {
                const sidebarItems = document.querySelectorAll('.sidebar-item');
                sidebarItems.forEach(item => {
                    item.addEventListener('click', closeSidebar);
                });
            }
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
