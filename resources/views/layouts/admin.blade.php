<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BoutiTrad') }} - {{ $title ?? 'Dashboard' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700/50">
                <div class="w-9 h-9 rounded-lg bg-indigo-500 flex items-center justify-center font-bold text-sm">BT</div>
                <div>
                    <div class="font-semibold text-sm">BoutiTrad</div>
                    <div class="text-xs text-slate-400">Gestion Commerciale</div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @php $user = auth()->user(); @endphp

                <x-admin.nav-link route="dashboard" icon="chart">Tableau de bord</x-admin.nav-link>

                @if($user->isSuperAdmin() || $user->isCommercial())
                    <x-admin.nav-link route="clients.index" icon="users">Clients</x-admin.nav-link>
                    <x-admin.nav-link route="orders.index" icon="cart">Commandes</x-admin.nav-link>
                @endif

                @if($user->isSuperAdmin() || $user->isGestionnaireStock())
                    <x-admin.nav-link route="products.index" icon="box">Produits</x-admin.nav-link>
                    <x-admin.nav-link route="stock.index" icon="warehouse">Stock</x-admin.nav-link>
                    <x-admin.nav-link route="categories.index" icon="tag">Catégories</x-admin.nav-link>
                @endif

                @if($user->isSuperAdmin() || $user->isCommercial())
                    <x-admin.nav-link route="commercials.index" icon="briefcase">Commerciaux</x-admin.nav-link>
                @endif

                @if($user->isSuperAdmin() || $user->isLivreur())
                    <x-admin.nav-link route="deliveries.index" icon="truck">Livraisons</x-admin.nav-link>
                @endif

                @if($user->isSuperAdmin())
                    <div class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Administration</div>
                    <x-admin.nav-link route="finance.index" icon="money">Finance</x-admin.nav-link>
                    <x-admin.nav-link route="reports.index" icon="report">Rapports</x-admin.nav-link>
                    <x-admin.nav-link route="users.index" icon="shield">Utilisateurs</x-admin.nav-link>
                    <x-admin.nav-link route="settings.index" icon="cog">Paramètres</x-admin.nav-link>
                @endif

                <x-admin.nav-link route="notifications.index" icon="bell">Notifications</x-admin.nav-link>
            </nav>

            <div class="p-4 border-t border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-medium">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $user->name }}</div>
                        <div class="text-xs text-slate-400">{{ $user->role->label() }}</div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-cloak></div>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="text-lg font-semibold text-slate-800">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if(auth()->user()->unreadNotifications->count())
                                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-100">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
