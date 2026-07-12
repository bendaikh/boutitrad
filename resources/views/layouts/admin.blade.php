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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|amiri:400,700|scheherazade-new:400,700|cormorant-garamond:400,600,700|lateef:400,700|aref-ruqaa:400,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $openMenu = '';
    if (request()->routeIs('products.*', 'categories.*', 'stock.*')) {
        $openMenu = 'stock';
    } elseif (request()->routeIs('orders.*', 'sales.*')) {
        $openMenu = 'ventes';
    } elseif (request()->routeIs('clients.*')) {
        $openMenu = 'clients';
    } elseif (request()->routeIs('reports.*', 'charges.*')) {
        $openMenu = 'etat';
    } elseif (request()->routeIs('deliveries.*')) {
        $openMenu = 'livraison';
    } elseif (request()->routeIs('settings.*', 'users.*', 'commercials.*')) {
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
               class="admin-sidebar fixed inset-y-0 left-0 z-50 transform transition-transform duration-200 ease-in-out lg:translate-x-0">
            <div class="admin-sidebar-pattern" aria-hidden="true"></div>

            <div class="admin-sidebar-header">
                <div class="admin-sidebar-logo-wrap">
                    <x-admin.logo class="h-16 w-16 mx-auto rounded-full object-cover ring-2 ring-gold-400/50 shadow-lg" />
                    <div class="admin-sidebar-flag">
                        <x-admin.morocco-flag class="h-4 w-6" />
                    </div>
                </div>
                <div class="admin-sidebar-brand">Beldi-Malaki</div>
                <div class="admin-sidebar-tagline">Habits traditionnels · بلدي ملكي</div>
                <div class="admin-sidebar-ornament" aria-hidden="true"></div>
            </div>

            <nav
                class="admin-sidebar-nav"
                x-init="$nextTick(() => { const scroll = sessionStorage.getItem('adminNavScroll'); if (scroll) $el.scrollTop = parseInt(scroll); })"
                @scroll.passive="sessionStorage.setItem('adminNavScroll', $el.scrollTop)"
            >
                @php $user = auth()->user(); @endphp

                @if($user->hasPermission('dashboard.access'))
                <x-admin.nav-link route="dashboard" icon="chart" :featured="true">BELDI-MALAKI</x-admin.nav-link>
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
                        @if($user->canManageStockCatalog() && $user->hasAnyPermission(['products.view', 'products.create', 'products.update', 'products.delete']))
                            <x-admin.nav-sublink route="products.index" icon="box">Produits</x-admin.nav-sublink>
                        @endif
                        @if($user->canManageStockCatalog() && $user->hasAnyPermission(['categories.view', 'categories.create', 'categories.update', 'categories.delete']))
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
                        :active="request()->routeIs('orders.*', 'sales.*')"
                        :open="$openMenu === 'ventes'"
                    >
                        @if($user->hasAnyPermission(['orders.view', 'orders.validate', 'orders.create', 'orders.update', 'orders.delete']))
                            <x-admin.nav-sublink route="orders.index" icon="cart">Commandes</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['sales.balance.view', 'sales.balance.print']))
                            <x-admin.nav-sublink route="sales.balance" icon="money">Balance</x-admin.nav-sublink>
                        @endif
                        @if($user->hasAnyPermission(['payments.view', 'payments.create', 'payments.update', 'payments.delete']))
                            <x-admin.nav-sublink route="sales.payments" icon="payment">Paie Commerciaux</x-admin.nav-sublink>
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
                        :active="request()->routeIs('reports.*', 'charges.*')"
                        :open="$openMenu === 'etat'"
                    >
                        <x-admin.nav-sublink route="reports.index" icon="report">Rapports</x-admin.nav-sublink>
                        <x-admin.nav-sublink route="charges.index" icon="money">Charge</x-admin.nav-sublink>
                    </x-admin.nav-group>
                @endif

                @if($user->canAccessConfigurationModule())
                    <x-admin.nav-group
                        label="Configuration"
                        menu-key="configuration"
                        icon="cog"
                        :active="request()->routeIs('settings.*', 'users.*', 'commercials.*')"
                        :open="$openMenu === 'configuration'"
                    >
                        @if($user->hasAnyPermission(['commercials.view', 'commercials.create', 'commercials.update', 'commercials.delete']))
                            <x-admin.nav-sublink route="commercials.index" icon="briefcase">{{ $user->isCommercial() ? 'Mon activité' : 'Commerciaux' }}</x-admin.nav-sublink>
                        @endif
                        @if($user->isSuperAdmin())
                            <x-admin.nav-sublink route="settings.index" icon="building">Fiche Société</x-admin.nav-sublink>
                            <x-admin.nav-sublink route="users.index" :match="['users.index', 'users.create', 'users.edit']" icon="shield">Utilisateurs</x-admin.nav-sublink>
                            <x-admin.nav-sublink route="settings.permissions" icon="lock">Autorisations</x-admin.nav-sublink>
                        @endif
                    </x-admin.nav-group>
                @endif
            </nav>

            <div class="admin-sidebar-profile">
                <a href="{{ route('profile.edit') }}" class="admin-sidebar-profile-link" title="Mon profil">
                    <img
                        src="{{ asset('images/gerant-profile.png') }}"
                        alt="Photo de profil Gérant"
                        class="admin-sidebar-profile-photo"
                    >
                    <div class="min-w-0 flex-1">
                        <p class="admin-sidebar-profile-name">Gérant</p>
                        <p class="admin-sidebar-profile-role">Direction générale</p>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-gold-200/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="admin-sidebar-actions" x-data="themeToggle()">
                <button
                    type="button"
                    @click="toggle()"
                    class="admin-sidebar-action-btn"
                    :title="isDark() ? 'Mode clair' : 'Mode sombre'"
                >
                    <svg x-show="!isDark()" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="isDark()" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="admin-sidebar-action-label" x-text="isDark() ? 'Clair' : 'Sombre'"></span>
                </button>
                <a href="{{ route('notifications.index') }}" class="admin-sidebar-action-btn relative" title="Notifications">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="admin-sidebar-action-label">Alertes</span>
                    @if($unreadCount = auth()->user()->unreadNotifications->count())
                        <span class="admin-sidebar-action-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="admin-sidebar-action-btn w-full" title="Déconnexion">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="admin-sidebar-action-label">Sortir</span>
                    </button>
                </form>
            </div>

            <div class="admin-sidebar-footer">
                <x-admin.morocco-flag class="h-3.5 w-5" />
                <span class="admin-sidebar-footer-text">Fièrement marocain</span>
            </div>
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
                            <div class="admin-header-flag hidden sm:block">
                                <x-admin.morocco-flag class="h-5 w-8" />
                            </div>
                            <div>
                                <h1 class="beldi-malaki-nav-title">BELDI-MALAKI</h1>
                                <p class="beldi-malaki-nav-welcome">Bienvenue — l'univers des habits traditionnels marocains</p>
                            </div>
                        @else
                            <div class="flex items-center gap-2.5">
                                <div class="admin-header-flag hidden sm:block">
                                    <x-admin.morocco-flag class="h-4 w-7" />
                                </div>
                                <h1 class="text-lg font-tradition font-bold text-royal dark:text-gold-200 tracking-wide">{{ $title ?? 'Dashboard' }}</h1>
                            </div>
                        @endif
                    </div>
                    <div class="admin-header-arabic hidden sm:block" dir="rtl" lang="ar">
                        <p class="admin-header-arabic-line hidden md:block">مَرْحَباً بِكُمْ</p>
                        <p class="admin-header-arabic-brand">بَلَدِي مَلَكِي</p>
                    </div>
                </div>
            </header>

            <main @class([
                'flex-1 min-h-0 p-4 sm:p-6 flex flex-col',
                'overflow-hidden' => $fullHeight,
                'overflow-y-auto' => ! $fullHeight,
            ])>
                @if(session('success'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        x-init="setTimeout(() => show = false, 2000)"
                        class="shrink-0 mb-4 admin-flash-success"
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
                        class="shrink-0 mb-4 admin-flash-error"
                    >{{ session('error') }}</div>
                @endif

                <div @class(['flex-1 min-h-0 flex flex-col' => $fullHeight])>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
