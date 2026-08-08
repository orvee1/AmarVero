<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="shopping-bag" :href="route('account.orders')" :current="request()->routeIs('account.orders*')" wire:navigate>
                    {{ __('Orders') }}
                </flux:navbar.item>
                <flux:navbar.item icon="map-pin" :href="route('account.addresses')" :current="request()->routeIs('account.addresses')" wire:navigate>
                    {{ __('Addresses') }}
                </flux:navbar.item>
                <flux:navbar.item icon="star" :href="route('account.reviews')" :current="request()->routeIs('account.reviews')" wire:navigate>
                    {{ __('Reviews') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Shop')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="shopping-bag" :href="route('shop')" :label="__('Shop')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Wishlist')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="heart" :href="route('wishlist')" :label="__('Wishlist')" />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Account')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('account.orders')" :current="request()->routeIs('account.orders*')" wire:navigate>
                        {{ __('Orders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('account.addresses')" :current="request()->routeIs('account.addresses')" wire:navigate>
                        {{ __('Addresses') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="star" :href="route('account.reviews')" :current="request()->routeIs('account.reviews')" wire:navigate>
                        {{ __('Reviews') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="heart" :href="route('wishlist')" :current="request()->routeIs('wishlist')" wire:navigate>
                        {{ __('Wishlist') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="shopping-bag" :href="route('shop')">
                    {{ __('Shop') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cog" :href="route('profile.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
