@props([
    'title' => null,
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['robots' => 'noindex, nofollow'])
    </head>
    <body
        x-data="{ sidebarOpen: false, sidebarCollapsed: false }"
        class="min-h-screen bg-zinc-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white"
    >
        <a href="#admin-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-zinc-950 focus:shadow-lg">
            {{ __('Skip to admin content') }}
        </a>

        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-zinc-950/50 lg:hidden"
            x-on:click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <aside
            x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none dark:border-white/10 dark:bg-zinc-900"
        >
            <div class="flex min-h-20 items-center justify-between gap-3 border-b border-zinc-200 px-4 dark:border-white/10">
                <x-brand-lockup :href="route('admin.dashboard')" />

                <button
                    type="button"
                    class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500 lg:hidden dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white"
                    x-on:click="sidebarOpen = false"
                    aria-label="{{ __('Close admin navigation') }}"
                >
                    <span aria-hidden="true">X</span>
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" aria-label="{{ __('Admin navigation') }}">
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Workspace') }}</p>
                    <div class="mt-2 grid gap-1">
                        @can('dashboard.view')
                            <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                {{ __('Overview') }}
                            </x-admin.nav-link>
                        @endcan

                        <x-admin.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Customer dashboard') }}
                        </x-admin.nav-link>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Catalog') }}</p>
                    <div class="mt-2 grid gap-1">
                        @can('brands.view')
                            <x-admin.nav-link :href="route('admin.catalog.brands')" :active="request()->routeIs('admin.catalog.brands')">
                                {{ __('Brands') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('categories.view')
                            <x-admin.nav-link :href="route('admin.catalog.categories')" :active="request()->routeIs('admin.catalog.categories')">
                                {{ __('Categories') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('collections.view')
                            <x-admin.nav-link :href="route('admin.catalog.collections')" :active="request()->routeIs('admin.catalog.collections')">
                                {{ __('Collections') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('attributes.view')
                            <x-admin.nav-link :href="route('admin.catalog.attributes')" :active="request()->routeIs('admin.catalog.attributes')">
                                {{ __('Attributes') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('products.view')
                            <x-admin.nav-link :href="route('admin.catalog.products')" :active="request()->routeIs('admin.catalog.products')">
                                {{ __('Products') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('product-variants.view')
                            <x-admin.nav-link :href="route('admin.catalog.variants')" :active="request()->routeIs('admin.catalog.variants')">
                                {{ __('Variants') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('product-images.view')
                            <x-admin.nav-link :href="route('admin.catalog.images')" :active="request()->routeIs('admin.catalog.images')">
                                {{ __('Images') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('inventory.view')
                            <x-admin.nav-link :href="route('admin.catalog.inventory')" :active="request()->routeIs('admin.catalog.inventory')">
                                {{ __('Inventory') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('size-guides.view')
                            <x-admin.nav-link :href="route('admin.catalog.size-guides')" :active="request()->routeIs('admin.catalog.size-guides')">
                                {{ __('Size guides') }}
                            </x-admin.nav-link>
                        @endcan
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Operations') }}</p>
                    <div class="mt-2 grid gap-1">
                        @can('orders.view')
                            <x-admin.nav-link :href="route('admin.operations.orders')" :active="request()->routeIs('admin.operations.orders')">
                                {{ __('Orders') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('customers.view')
                            <x-admin.nav-link :href="route('admin.operations.customers')" :active="request()->routeIs('admin.operations.customers')">
                                {{ __('Customers') }}
                            </x-admin.nav-link>
                        @endcan
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Marketing') }}</p>
                    <div class="mt-2 grid gap-1">
                        @if (auth()->user()->can('campaigns.view') || auth()->user()->can('coupons.view') || auth()->user()->can('newsletter.view'))
                            <x-admin.nav-link :href="route('admin.marketing')" :active="request()->routeIs('admin.marketing')">
                                {{ __('Campaigns and coupons') }}
                            </x-admin.nav-link>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Content') }}</p>
                    <div class="mt-2 grid gap-1">
                        @can('announcement-bars.view')
                            <x-admin.nav-link :href="route('admin.content.announcements')" :active="request()->routeIs('admin.content.announcements')">
                                {{ __('Announcements') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('navigation-menus.view')
                            <x-admin.nav-link :href="route('admin.content.navigation')" :active="request()->routeIs('admin.content.navigation')">
                                {{ __('Navigation') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('homepage-sections.view')
                            <x-admin.nav-link :href="route('admin.content.homepage')" :active="request()->routeIs('admin.content.homepage')">
                                {{ __('Homepage') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('pages.view')
                            <x-admin.nav-link :href="route('admin.content.library')" :active="request()->routeIs('admin.content.library')">
                                {{ __('Library') }}
                            </x-admin.nav-link>
                        @endcan

                        @can('footer-sections.view')
                            <x-admin.nav-link :href="route('admin.content.footer')" :active="request()->routeIs('admin.content.footer')">
                                {{ __('Footer') }}
                            </x-admin.nav-link>
                        @endcan
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Settings') }}</p>
                    <div class="mt-2 grid gap-1">
                        @if (auth()->user()->can('settings.view') || auth()->user()->can('shipping-settings.view'))
                            <x-admin.nav-link :href="route('admin.settings.store')" :active="request()->routeIs('admin.settings.store')">
                                {{ __('Store settings') }}
                            </x-admin.nav-link>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Account') }}</p>
                    <div class="mt-2 grid gap-1">
                        <x-admin.nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                            {{ __('Profile settings') }}
                        </x-admin.nav-link>

                        <x-admin.nav-link :href="route('home')" :active="request()->routeIs('home')">
                            {{ __('View storefront') }}
                        </x-admin.nav-link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-zinc-200 p-4 dark:border-white/10">
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                    <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-zinc-600 dark:text-zinc-300">{{ auth()->user()->email }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="w-full">
                        {{ __('Log out') }}
                    </x-ui.button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-zinc-950/90">
                <div class="flex min-h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-900 hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500 lg:hidden dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
                            x-on:click="sidebarOpen = true"
                        >
                            {{ __('Menu') }}
                        </button>

                        <div class="min-w-0">
                            @if ($breadcrumbs)
                                <nav class="mb-1 flex flex-wrap items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                                    @foreach ($breadcrumbs as $label => $url)
                                        @if ($url)
                                            <a class="hover:text-zinc-900 dark:hover:text-white" href="{{ $url }}">{{ $label }}</a>
                                            <span aria-hidden="true">/</span>
                                        @else
                                            <span>{{ $label }}</span>
                                        @endif
                                    @endforeach
                                </nav>
                            @endif

                            <h1 class="truncate text-xl font-semibold text-zinc-950 dark:text-white">{{ $title ?? __('Admin') }}</h1>
                        </div>
                    </div>

                    @isset($actions)
                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>
            </header>

            <main id="admin-content" class="px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
