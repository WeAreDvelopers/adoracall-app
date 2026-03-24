<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() ?? '' }}">
    <title>@yield('title', 'AdoraCall')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#e6edf5',
                            100: '#ccdaeb',
                            200: '#99b5d6',
                            300: '#6690c2',
                            400: '#336bad',
                            500: '#00194A',
                            600: '#001440',
                            700: '#000f33',
                        },
                        accent: {
                            50: '#fff9e6',
                            100: '#fff0b3',
                            200: '#ffe680',
                            300: '#ffdb4d',
                            400: '#ffd11a',
                            500: '#FFAE00',
                            600: '#e69d00',
                            700: '#cc8b00',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @yield('styles')
</head>
<body class="font-sans bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen flex items-center justify-center p-4">

    @yield('content')

    <!-- Scripts essenciais -->
    <script>
        const originalLocalStorage = window.localStorage;
        window.safeParseJSON = function(str, defaultValue = null) {
            try {
                if (!str || str === 'undefined' || str === 'null' || typeof str === 'undefined') {
                    return defaultValue;
                }
                return JSON.parse(str);
            } catch (e) {
                return defaultValue;
            }
        };
    </script>
    <script src="{{ asset('js/auth-interceptor.js') }}"></script>
    <script src="{{ asset('js/config.js') }}"></script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
