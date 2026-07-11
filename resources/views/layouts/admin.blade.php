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
    <link rel="apple-touch-icon" sizes="192x192" href="{{ param_image('logo_path', 'logo.jpg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administration - Plateforme de Gestion</title>
    <link rel="icon" href="{{ param_image('logo_path', 'logo.jpg') }}" type="image/jpeg">

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
            background-image: url('{{ param_image('banner_path', 'magasinier-bg.png') }}');
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
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 glass-panel transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col justify-between overflow-y-auto">
            <div>
                <div class="flex items-center justify-center h-40 border-b border-white/40 px-4 relative">
                    <img src="{{ param_image('logo_path', 'logo.jpg') }}" alt="Logo" class="h-30 w-auto object-contain">

                    <!-- Bouton pour fermer (Mobile) -->
                    <button @click="sidebarOpen = false" class="lg:hidden absolute top-4 right-4 text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>

                <nav class="mt-6 px-4 space-y-2">
                    <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="ri-dashboard-line text-xl mr-3"></i>
                        <span>Tableau de bord</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="ri-team-line text-xl mr-3"></i>
                        <span>Employés & Accès</span>
                    </a>

                    <a href="{{ route('admin.payroll.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}">
                        <i class="ri-file-list-3-line text-xl mr-3"></i>
                        <span>Paie des employés</span>
                    </a>

                    <a href="{{ route('admin.produits.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.produits.*') ? 'active' : '' }}">
                        <i class="ri-price-tag-3-line text-xl mr-3"></i>
                        <span>Produits</span>
                    </a>

                    <a href="{{ route('admin.fournisseurs.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.fournisseurs.*') || request()->routeIs('admin.grossistes.*') ? 'active' : '' }}">
                        <i class="ri-truck-line text-xl mr-3"></i>
                        <span>Fournisseurs & Grossistes</span>
                    </a>

                    <a href="{{ route('admin.achats.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.achats.*') ? 'active' : '' }}">
                        <i class="ri-shopping-cart-2-line text-xl mr-3"></i>
                        <span>Achats & Stocks</span>
                    </a>

                    <a href="{{ route('admin.boutiques.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.boutiques.*') ? 'active' : '' }}">
                        <i class="ri-store-3-line text-xl mr-3"></i>
                        <span>Boutiques & Trésorerie</span>
                    </a>

                    <a href="{{ route('admin.profile.edit') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                        <i class="ri-user-line text-xl mr-3"></i>
                        <span>Mon profil</span>
                    </a>

                    <a href="{{ route('admin.rapports.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.rapports.*') ? 'active' : '' }}">
                        <i class="ri-line-chart-line text-xl mr-3"></i>
                        <span>Rapports</span>
                    </a>

                    <a href="{{ route('admin.logs.index') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <i class="ri-history-line text-xl mr-3"></i>
                        <span>Historique & Logs</span>
                    </a>

                    <a href="{{ route('admin.parametres.edit') }}" class="nav-item flex items-center px-4 py-3 text-slate-600 rounded-lg {{ request()->routeIs('admin.parametres.*') ? 'active' : '' }}">
                        <i class="ri-settings-3-line text-xl mr-3"></i>
                        <span>Paramètres</span>
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
                    <span class="text-sm font-medium">Bonjour, {{ Auth::user()->nom_utilisateur ?? 'SuperAdmin' }}</span>
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="ri-user-settings-line text-xl"></i>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-transparent p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @include('components.pwa-status')
</body>
</html>
