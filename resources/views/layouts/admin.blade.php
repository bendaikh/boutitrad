<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BELDI-MALAKI - {{ $title ?? 'Dashboard' }}</title>
    <script>
        (function () {
            var stored = localStorage.getItem('boutitrad-theme');
            var dark = stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|amiri:400,700|scheherazade-new:400,700|cormorant-garamond:400,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $openMenu = '';
    if (request()->routeIs('products.*', 'categories.*', 'stock.*')) {
        $openMenu = 'stock';
    } elseif (request()->routeIs('orders.*', 'commercials.*', 'sales.*')) {
        $openMenu = 'ventes';
    } elseif (request()->routeIs('clients.*')) {
        $openMenu = 'clients';
    } elseif (request()->routeIs('reports.*', 'finance.*')) {
        $openMenu = 'etat';
    } elseif (request()->routeIs('deliveries.*')) {
        $openMenu = 'livraison';
    } elseif (request()->routeIs('settings.*', 'users.*')) {
        $openMenu = 'configuration';
    }
@endphp
<body
    class="font-sans antialiased bg-surface-muted dark:bg-slate-950 text-slate-900 dark:text-slate-100 h-screen overflow-hidden"
    x-data="{ sidebarOpen: false, openMenu: '{{ $openMenu }}' || sessionStorage.getItem('adminOpenMenu') || '' }"
    x-init="if ('{{ $openMenu }}') { openMenu = '{{ $openMenu }}'; sessionStorage.setItem('adminOpenMenu', openMenu); }"
>
    <div class="h-screen overflow-hidden">
        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-700 transform transition-transform duration-200 ease-in-out lg:translate-x-0 flex flex-col shadow-sm h-screen">
            <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-700 text-center shrink-0">
                <x-admin.logo class="h-16 w-16 mx-auto rounded-full object-cover" />
                <div style="font-family: 'Scheherazade New', 'Amiri', serif; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.1em;" class="mt-3 admin-brand-title">Beldi-Malaki</div>
                <div style="font-family: 'Scheherazade New', 'Amiri', serif; text-decoration: underline; text-underline-offset: 3px; font-weight: 700;" class="text-xs mt-1 admin-brand-title">Direction générale</div>
            </div>

            <nav
                class="flex-1 overflow-y-auto min-h-0 px-4 py-4 space-y-1"
                x-init="$nextTick(() => { const scroll = sessionStorage.getItem('adminNavScroll'); if (scroll) $el.scrollTop = parseInt(scroll); })"
                @scroll.passive="sessionStorage.setItem('adminNavScroll', $el.scrollTop)"
            >
                @php $user = auth()->user(); @endphp

                @if($user->hasPermission('dashboard.access'))
                <x-admin.nav-link route="dashboard" icon="chart">BELDI-MALAKI</x-admin.nav-link>
                @endif

                @if($user->canAccessClientsModule())
                    <x-admin.nav-group
                        label="Clients"
                        menu-key="clients"
                        icon="users"
                        :active="request()->routeIs('clients.*')"
                        :open="$openMenu === 'clients'"
                    >
                        @if($user->hasAnyPermission(['clients.create', 'clients.view', 'clients.update', 'clients.delete']))
                            <x-admin.nav-sublink route="clients.index" :match="['clients.index', 'clients.show', 'clients.create', 'clients.edit']" icon="users">Fiche client</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['clients.balance.view', 'clients.balance.print']))
                            <x-admin.nav-sublink route="clients.balances" icon="money">Balance client</x-admin.nav-sublink>
                        @endif
                    </x-admin.nav-group>
                @endif

                @if($user->canAccessStockModule())
                    <x-admin.nav-group
                        label="Stock"
                        menu-key="stock"
                        icon="warehouse"
                        :active="request()->routeIs('products.*', 'categories.*', 'stock.*')"
                        :open="$openMenu === 'stock'"
                    >
                        @if($user->hasAnyPermission(['products.view', 'products.create', 'products.update', 'products.delete']))
                            <x-admin.nav-sublink route="products.index" icon="box">Produits</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['categories.view', 'categories.create', 'categories.update', 'categories.delete']))
                            <x-admin.nav-sublink route="categories.index" icon="tag">Catégorie</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['stock.view', 'stock.print']))
                            <x-admin.nav-sublink route="stock.index" icon="warehouse">Stock</x-admin.nav-sublink>
                        @endif
                    </x-admin.nav-group>
                @endif

                @if($user->canAccessVentesModule())
                    <x-admin.nav-group
                        label="Ventes"
                        menu-key="ventes"
                        icon="cart"
                        :active="request()->routeIs('orders.*', 'commercials.*', 'sales.*')"
                        :open="$openMenu === 'ventes'"
                    >
                        @if($user->hasAnyPermission(['orders.view', 'orders.validate', 'orders.create', 'orders.update', 'orders.delete']))
                            <x-admin.nav-sublink route="orders.index" icon="cart">Commandes</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['commercials.view', 'commercials.create', 'commercials.update', 'commercials.delete']))
                            <x-admin.nav-sublink route="commercials.index" icon="briefcase">{{ $user->isCommercial() ? 'Mon activité' : 'Commerciaux' }}</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['sales.balance.view', 'sales.balance.print']))
                            <x-admin.nav-sublink route="sales.balance" icon="money">Balance</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['payments.view', 'payments.create', 'payments.update', 'payments.delete']))
                            <x-admin.nav-sublink route="sales.payments" icon="payment">Paiement</x-admin.nav-sublink>
                        @endif
                    </x-admin.nav-group>
                @endif

                @if($user->isSuperAdmin() || $user->isLivreur())
                    <x-admin.nav-group
                        label="Livraison"
                        menu-key="livraison"
                        icon="truck"
                        :active="request()->routeIs('deliveries.*')"
                        :open="$openMenu === 'livraison'"
                    >
                        <x-admin.nav-sublink route="deliveries.partners" icon="partner">Partenaire</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="deliveries.transport" icon="truck">Transport</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="deliveries.livreurs" icon="users">Livreur</x-admin.nav-sublink>
                    </x-admin.nav-group>
                @endif

                @if($user->isSuperAdmin())
                    <x-admin.nav-group
                        label="Etat"
                        menu-key="etat"
                        icon="report"
                        :active="request()->routeIs('reports.*', 'finance.*')"
                        :open="$openMenu === 'etat'"
                    >
                        <x-admin.nav-sublink route="reports.index" icon="report">Rapports</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="finance.index" icon="money">Finance</x-admin.nav-sublink>
                    </x-admin.nav-group>

                    <x-admin.nav-group
                        label="Configuration"
                        menu-key="configuration"
                        icon="cog"
                        :active="request()->routeIs('settings.*', 'users.*')"
                        :open="$openMenu === 'configuration'"
                    >
                        <x-admin.nav-sublink route="settings.index" icon="building">Fiche Société</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="users.index" :match="['users.index', 'users.create', 'users.edit']" icon="shield">Utilisateurs</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="settings.permissions" icon="lock">Autorisations</x-admin.nav-sublink>
                    </x-admin.nav-group>
                @endif
            </nav>
        </aside>

        {{-- Overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-cloak></div>

        {{-- Main --}}
        <div class="flex flex-col min-w-0 min-h-0 h-screen overflow-hidden lg:ml-64">
            <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 shrink-0 z-30 shadow-sm">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden admin-icon-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        @if(($title ?? '') === 'BELDI-MALAKI')
                            <div>
                                <h1 style="font-family: 'Scheherazade New', 'Amiri', serif; font-size: 1.75rem; font-weight: 700; letter-spacing: 0.14em;" class="leading-tight admin-brand-title">BELDI-MALAKI</h1>
                                <p style="font-family: 'Scheherazade New', 'Amiri', serif; text-decoration: underline; text-underline-offset: 3px;" class="text-sm mt-1 max-w-xl leading-snug admin-brand-title">Bienvenus sur votre plateforme Beldi-Malaki, l'univers du BELDI !</p>
                            </div>
                        @else
                            <h1 class="text-lg font-bold text-brand-800 dark:text-brand-300">{{ $title ?? 'Dashboard' }}</h1>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4" x-data="themeToggle()">
                        <button
                            type="button"
                            @click="toggle()"
                            class="admin-icon-btn"
                            :title="isDark() ? 'Mode clair' : 'Mode sombre'"
                        >
                            <svg x-show="!isDark()" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <svg x-show="isDark()" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </button>
                        <a href="{{ route('notifications.index') }}" class="relative admin-icon-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount = auth()->user()->unreadNotifications->count())
                                <span class="absolute -top-0.5 -right-0.5 min-w-[1.125rem] h-[1.125rem] px-1 flex items-center justify-center text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="admin-icon-btn" title="Déconnexion">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                        <a href="{{ route('profile.edit') }}" class="rounded-full hover:ring-2 hover:ring-brand-200 dark:hover:ring-brand-700 transition-shadow" title="Mon profil">
                            <x-admin.user-avatar :user="$user" />
                        </a>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6">
                @if(session('success'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        x-init="setTimeout(() => show = false, 2000)"
                        class="mb-4 admin-flash-success"
                    >{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        x-init="setTimeout(() => show = false, 4000)"
                        class="mb-4 admin-flash-error"
                    >{{ session('error') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
