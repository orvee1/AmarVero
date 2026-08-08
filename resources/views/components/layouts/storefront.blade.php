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

        @if ($storefrontAnnouncement)
            <div class="border-b border-zinc-200 bg-zinc-950 text-white dark:border-white/10">
                <x-ui.container class="flex min-h-10 flex-wrap items-center justify-center gap-2 text-center text-sm font-medium">
                    <span>{{ $storefrontAnnouncement->message }}</span>

                    @if ($storefrontAnnouncement->link_label && $storefrontAnnouncement->link_url)
                        <a class="underline-offset-4 hover:underline" href="{{ $storefrontAnnouncement->link_url }}">
                            {{ $storefrontAnnouncement->link_label }}
                        </a>
                    @endif
                </x-ui.container>
            </div>
        @else
            <div class="border-b border-zinc-200 bg-zinc-950 text-white dark:border-white/10">
                <x-ui.container class="flex min-h-10 items-center justify-center text-center text-sm font-medium">
                    {{ __('Premium footwear essentials for city days, work hours, and weekend plans.') }}
                </x-ui.container>
            </div>
        @endif

        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-zinc-950/90">
            <x-ui.container class="flex min-h-20 items-center justify-between gap-4">
                <x-brand-lockup />

                <nav class="hidden items-center gap-1 md:flex" aria-label="{{ __('Primary navigation') }}">
                    @if ($storefrontNavigation && $storefrontNavigation->items->isNotEmpty())
                        @foreach ($storefrontNavigation->items as $item)
                            @if (($item->meta['desktop_visible'] ?? true) === true)
                                <div class="relative">
                                    <x-ui.button
                                        variant="subtle"
                                        :href="$storefrontContent->navigationItemUrl($item)"
                                        :target="$item->opens_new_tab ? '_blank' : null"
                                        :rel="$item->opens_new_tab ? 'noopener noreferrer' : null"
                                    >
                                        {{ $item->label }}
                                    </x-ui.button>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <x-ui.button variant="subtle" :href="route('home')">{{ __('Home') }}</x-ui.button>
                    @endif

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
                        @if ($storefrontNavigation && $storefrontNavigation->items->isNotEmpty())
                            @foreach ($storefrontNavigation->items as $item)
                                @if (($item->meta['mobile_visible'] ?? true) === true)
                                    <a
                                        class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10"
                                        href="{{ $storefrontContent->navigationItemUrl($item) }}"
                                        @if ($item->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        {{ $item->label }}
                                    </a>

                                    @foreach ($item->children as $child)
                                        @if (($child->meta['mobile_visible'] ?? true) === true)
                                            <a
                                                class="rounded-md px-5 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/10"
                                                href="{{ $storefrontContent->navigationItemUrl($child) }}"
                                                @if ($child->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                            >
                                                {{ $child->label }}
                                            </a>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @else
                            <a class="rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10" href="{{ route('home') }}">{{ __('Home') }}</a>
                        @endif

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
            <x-ui.container class="grid gap-8 py-10 lg:grid-cols-[1.2fr_2fr]">
                <div>
                    <x-brand-lockup />
                    <p class="mt-4 max-w-xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('Amarvero is being built as a focused footwear commerce platform with secure accounts, dynamic merchandising, and reliable operations.') }}
                    </p>

                    @if ($storefrontSocialLinks->isNotEmpty())
                        <nav class="mt-5 flex flex-wrap gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300" aria-label="{{ __('Social links') }}">
                            @foreach ($storefrontSocialLinks as $socialLink)
                                <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer">
                                    {{ $socialLink->label }}
                                </a>
                            @endforeach
                        </nav>
                    @endif
                </div>

                @if ($storefrontFooterSections->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($storefrontFooterSections as $footerSection)
                            <nav aria-label="{{ $footerSection->title }}">
                                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $footerSection->title }}</h2>
                                <div class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    @foreach ($footerSection->links as $footerLink)
                                        <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ $footerLink->url }}" @if ($footerLink->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                            {{ $footerLink->label }}
                                        </a>
                                    @endforeach
                                </div>
                            </nav>
                        @endforeach
                    </div>
                @else
                    <nav class="flex flex-wrap gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300" aria-label="{{ __('Footer navigation') }}">
                        <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>

                        @auth
                            <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('dashboard') }}">{{ __('My account') }}</a>
                        @else
                            <a class="rounded-md px-2 py-1 hover:text-zinc-950 dark:hover:text-white" href="{{ route('login') }}">{{ __('Sign in') }}</a>
                        @endauth
                    </nav>
                @endif
            </x-ui.container>
        </footer>

        @fluxScripts
    </body>
</html>
