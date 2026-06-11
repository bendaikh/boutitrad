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
<body class="font-sans antialiased bg-surface-muted" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-sm">
            <div class="px-6 py-6 border-b border-slate-100">
                <div class="font-bold text-lg text-brand-800 tracking-tight">Bouti-Trad</div>
                <div class="text-xs text-slate-400 mt-0.5">Service Commercial</div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
                @php $user = auth()->user(); @endphp

                <x-admin.nav-link route="dashboard" icon="chart">TB-Bord</x-admin.nav-link>

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
                    <x-admin.nav-link route="deliveries.index" icon="truck">Livraison</x-admin.nav-link>
                @endif

                @if($user->isSuperAdmin())
                    <div class="pt-4 pb-2 px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Administration</div>
                    <x-admin.nav-link route="finance.index" icon="money">Finance</x-admin.nav-link>
                    <x-admin.nav-link route="reports.index" icon="report">Rapports</x-admin.nav-link>
                    <x-admin.nav-link route="users.index" icon="shield">Utilisateurs</x-admin.nav-link>
                    <x-admin.nav-link route="settings.index" icon="cog">Configuration</x-admin.nav-link>
                @endif

                <x-admin.nav-link route="notifications.index" icon="bell">Notifications</x-admin.nav-link>
            </nav>
        </aside>

        {{-- Overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-cloak></div>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="text-lg font-bold text-brand-800">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if(auth()->user()->unreadNotifications->count())
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors" title="Déconnexion">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                        <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center text-sm font-semibold text-white ring-2 ring-brand-100">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
