<x-layouts.storefront :title="__('Premium footwear for daily movement')">
    <section class="bg-white dark:bg-zinc-950">
        <x-ui.container class="grid min-h-[calc(100vh-7.5rem)] items-center gap-10 py-12 lg:grid-cols-[0.9fr_1.1fr] lg:py-16">
            <div class="max-w-2xl">
                <x-ui.badge tone="teal">{{ __('Original Amarvero storefront') }}</x-ui.badge>

                <h1 class="mt-6 text-4xl font-semibold leading-tight text-zinc-950 sm:text-5xl lg:text-6xl dark:text-white">
                    {{ __('Footwear built for daily movement.') }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-8 text-zinc-600 dark:text-zinc-300">
                    {{ __('A refined commerce experience for modern shoes, secure customer accounts, and a catalog foundation ready for premium merchandising.') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <x-ui.button :href="route('dashboard')" size="lg">
                            {{ __('Open my account') }}
                        </x-ui.button>
                    @else
                        @if (Route::has('register'))
                            <x-ui.button :href="route('register')" size="lg">
                                {{ __('Create account') }}
                            </x-ui.button>
                        @endif

                        <x-ui.button :href="route('login')" variant="secondary" size="lg">
                            {{ __('Sign in') }}
                        </x-ui.button>
                    @endauth
                </div>
            </div>

            <div class="relative">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <img
                        src="{{ asset('images/storefront/hero-footwear.png') }}"
                        alt="{{ __('Black leather low-top sneakers on a clean studio surface.') }}"
                        width="1536"
                        height="1024"
                        class="aspect-[3/2] h-full w-full object-cover"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </x-ui.container>
    </section>

    <section class="border-y border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-zinc-900/50">
        <x-ui.container class="grid gap-6 py-12 md:grid-cols-3">
            <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Everyday pairs') }}</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    {{ __('Clean silhouettes and comfortable profiles for repeat wear across busy days.') }}
                </p>
            </article>

            <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Work-ready finish') }}</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    {{ __('Structured materials, calm colorways, and clear details for professional routines.') }}
                </p>
            </article>

            <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Weekend rotation') }}</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    {{ __('Versatile footwear stories that can expand into collections, campaigns, and launches.') }}
                </p>
            </article>
        </x-ui.container>
    </section>
</x-layouts.storefront>
