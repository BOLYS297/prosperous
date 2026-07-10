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
    <title>Caisse - Point de Vente</title>
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .nav-item {
            transition: all 0.2s ease-in-out;
        }
        .nav-item:hover {
            transform: translateX(5px);
            background: linear-gradient(90deg, rgba(59,130,246,0.1) 0%, rgba(255,255,255,0) 100%);
            border-left: 3px solid #3b82f6;
        }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(59,130,246,0.2) 0%, rgba(255,255,255,0) 100%);
            border-left: 3px solid #2563eb;
            font-weight: 600;
            color: #1e40af;
        }
        #bg-image{
            background-image: url('{{ asset('magasinier-bg.png') }}');
            background-size: cover;
            background-position: center;
            opacity: 80%;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 glass-panel transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col justify-between overflow-y-auto overscroll-y-contain">
            <div>
                <div class="flex items-center justify-between h-40 border-b border-white/40 px-4 relative">
                    <img src="{{ asset('logo.jpg') }}" alt="Logo" class="h-30 w-auto object-contain">

                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>

                <nav class="mt-6 px-4 space-y-2">
                    <a href="{{ route('boutiquier.dashboard') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('boutiquier.dashboard') ? 'active' : '' }}">
                        <i class="ri-store-2-line text-xl mr-3"></i>
                        <span>Point de Vente</span>
                    </a>

                    <a href="{{ route('boutiquier.ventes.historique') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('boutiquier.ventes.*') ? 'active' : '' }}">
                        <i class="ri-file-list-3-line text-xl mr-3"></i>
                        <span>Historique du Jour</span>
                    </a>

                    <a href="{{ route('boutiquier.transferts.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('boutiquier.transferts.*') ? 'active' : '' }}">
                        <i class="ri-truck-line text-xl mr-3"></i>
                        <span>Demandes de Stock</span>
                    </a>

                    <a href="{{ route('boutiquier.dettes.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('boutiquier.dettes.*') ? 'active' : '' }}">
                        <i class="ri-refund-2-line text-xl mr-3"></i>
                        <span>Recouvrement des Dettes</span>
                    </a>
                </nav>
            </div>

            <div class="p-4 border-t border-white/40">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-lg shadow-md hover:shadow-lg hover:from-red-600 hover:to-rose-600 transition-all duration-200">
                        <i class="ri-logout-box-r-line mr-2"></i> Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden relative">

            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 w-full h-64  -z-10 rounded-bl-[100px]" id="bg-image"></div>

            <!-- Top Header -->
            <header class="flex items-center justify-between px-6 py-4">
                <button @click.stop="sidebarOpen = !sidebarOpen" class="text-white lg:hidden">
                    <i class="ri-menu-line text-2xl"></i>
                </button>

                <div class="flex items-center space-x-4 text-white ml-auto">
                    <span class="text-sm font-medium">Bonjour, {{ Auth::user()->nom_utilisateur ?? 'Boutiquier' }}</span>
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="ri-store-2-line text-xl"></i>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-transparent p-6">
                @include('components.shift-countdown')
                @include('components.offline-queue-status')
                @include('components.offline-failed-status')
                @yield('content')
            </main>
        </div>
    </div>

    @include('components.pwa-status')
    @stack('modals')
</body>
</html>
