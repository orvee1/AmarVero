@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-zinc-950 focus:shadow-lg">
            {{ __('Skip to content') }}
        </a>

        <div class="border-b border-zinc-200 bg-zinc-950 text-white dark:border-white/10">
            <x-ui.container class="flex min-h-10 items-center justify-center text-center text-sm font-medium">
                {{ __('Premium footwear essentials for city days, work hours, and weekend plans.') }}
            </x-ui.container>
        </div>

        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-zinc-950/90">
            <x-ui.container class="flex min-h-20 items-center justify-between gap-4">
                <x-brand-lockup />

                <nav class="hidden items-center gap-1 md:flex" aria-label="{{ __('Primary navigation') }}">
                    <x-ui.button variant="subtle" :href="route('home')">{{ __('Home') }}</x-ui.button>

                    @auth
                        <x-ui.button variant="subtle" :href="route('dashboard')">{{ __('My account') }}</x-ui.button>
                    @else
                        <x-ui.button variant="subtle" :href="route('login')">{{ __('Sign in') }}</x-ui.button>

                        @if (Route::has('register'))
                            <x-ui.button variant="primary" :href="route('register')">{{ __('Create account') }}</x-ui.button>
                        @endif
                    @endauth
                </nav>

                <details class="relative md:hidden">
                    <summary class="flex min-h-10 cursor-pointer list-none items-center rounded-lg border border-zinc-300 px-3 text-sm font-semibold text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600 dark:border-white/15 dark:text-white">
                        {{ __('Menu') }}
                    </summary>

                    <nav class="absolute right-0 mt-3 grid w-52 gap-1 rounded-lg border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-zinc-900" aria-label="{{ __('Mobile navigation') }}">
                        <a class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10" href="{{ route('home') }}">{{ __('Home') }}</a>

                        @auth
                            <a class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10" href="{{ route('dashboard') }}">{{ __('My account') }}</a>
                        @else
                            <a class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10" href="{{ route('login') }}">{{ __('Sign in') }}</a>

                            @if (Route::has('register'))
                                <a class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10" href="{{ route('register') }}">{{ __('Create account') }}</a>
                            @endif
                        @endauth
                    </nav>
                </details>
            </x-ui.container>
        </header>

        <main id="main-content">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950">
            <x-ui.container class="grid gap-8 py-10 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <x-brand-lockup />
                    <p class="mt-4 max-w-xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('Amarvero is being built as a focused footwear commerce platform with secure accounts, dynamic merchandising, and reliable operations.') }}
                    </p>
                </div>

                <nav class="flex flex-wrap gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300" aria-label="{{ __('Footer navigation') }}">
                    <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>

                    @auth
                        <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('dashboard') }}">{{ __('My account') }}</a>
                    @else
                        <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('login') }}">{{ __('Sign in') }}</a>
                    @endauth
                </nav>
            </x-ui.container>
        </footer>

        @fluxScripts
    </body>
</html>
