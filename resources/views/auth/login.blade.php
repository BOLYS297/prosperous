<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Prosperous Motos">
    <link rel="manifest" href="/manifest.webmanifest" type="application/manifest+json">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('logo.jpg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Plateforme de Gestion</title>

    <!-- Prevent browser caching to avoid 419 Page Expired errors on back button -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body class="bg-slate-50 antialiased min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-blue-400/20 blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-indigo-500/20 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md p-6">
        <div class="glass-panel rounded-3xl p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 mb-4">
                    <i class="ri-store-2-fill text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800">Espace Sécurisé</h2>
                <p class="text-slate-500 mt-2">Connectez-vous pour accéder au système.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                    <div class="flex">
                        <i class="ri-error-warning-line text-lg mr-2"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-5">
                    <label for="nom_utilisateur" class="block text-sm font-medium text-slate-700 mb-2">Nom d'utilisateur</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-user-line text-slate-400"></i>
                        </div>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" class="block w-full pl-10 pr-3 py-3 border border-slate-300 rounded-xl bg-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="votre_identifiant" required autofocus>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-lock-line text-slate-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="block w-full pl-10 pr-12 py-3 border border-slate-300 rounded-xl bg-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="••••••••" required>
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors" id="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="ri-eye-line text-lg" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6 hidden">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-slate-700">Se souvenir de moi</label>
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5">
                    Connexion sécurisée <i class="ri-arrow-right-line ml-2"></i>
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-slate-500 mt-8">
            &copy; {{ date('Y') }} Plateforme de Gestion. Accès strictement réservé.
        </p>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('ri-eye-line');
                passwordIcon.classList.add('ri-eye-off-line');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('ri-eye-off-line');
                passwordIcon.classList.add('ri-eye-line');
            }
        }
    </script>
</body>
</html>
