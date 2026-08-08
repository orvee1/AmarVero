<x-layouts.storefront :title="__('Premium footwear for daily movement')">
    @if ($heroSlides->isNotEmpty())
        <section class="bg-white dark:bg-zinc-950">
            @foreach ($heroSlides->take(1) as $slide)
                <x-ui.container class="grid min-h-[calc(100vh-7.5rem)] items-center gap-10 py-12 lg:grid-cols-[0.9fr_1.1fr] lg:py-16">
                    <div class="max-w-2xl">
                        <x-ui.badge tone="teal">{{ __('Amarvero') }}</x-ui.badge>
                        <h1 class="mt-6 text-4xl font-semibold leading-tight text-zinc-950 sm:text-5xl lg:text-6xl dark:text-white">{{ $slide->title }}</h1>

                        @if ($slide->subtitle)
                            <p class="mt-5 max-w-xl text-base leading-8 text-zinc-600 dark:text-zinc-300">{{ $slide->subtitle }}</p>
                        @endif

                        @if ($slide->cta_label && $slide->cta_url)
                            <div class="mt-8">
                                <x-ui.button :href="$slide->cta_url" size="lg">{{ $slide->cta_label }}</x-ui.button>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <img
                            src="{{ $storefrontContent->mediaUrl($slide->image_path) }}"
                            alt="{{ $slide->meta['image_alt'] ?? $slide->title }}"
                            width="1536"
                            height="1024"
                            class="aspect-[3/2] h-full w-full object-cover"
                            fetchpriority="high"
                        >
                    </div>
                </x-ui.container>
            @endforeach
        </section>
    @else
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
                        <x-ui.button :href="route('shop')" size="lg">{{ __('Shop footwear') }}</x-ui.button>

                        @auth
                            <x-ui.button :href="route('dashboard')" variant="secondary" size="lg">{{ __('Open my account') }}</x-ui.button>
                        @else
                            @if (Route::has('register'))
                                <x-ui.button :href="route('register')" variant="secondary" size="lg">{{ __('Create account') }}</x-ui.button>
                            @endif

                            <x-ui.button :href="route('login')" variant="subtle" size="lg">{{ __('Sign in') }}</x-ui.button>
                        @endauth
                    </div>
                </div>

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
            </x-ui.container>
        </section>
    @endif

    @if ($promotionalBanners->isNotEmpty())
        <section class="border-y border-zinc-200 bg-zinc-950 text-white dark:border-white/10">
            <x-ui.container class="grid gap-6 py-10">
                @foreach ($promotionalBanners as $banner)
                    <article class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            @if ($banner->title)
                                <h2 class="text-2xl font-semibold">{{ $banner->title }}</h2>
                            @endif
                            @if ($banner->subtitle)
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-200">{{ $banner->subtitle }}</p>
                            @endif
                        </div>
                        @if ($banner->cta_label && $banner->cta_url)
                            <x-ui.button :href="$banner->cta_url" variant="secondary">{{ $banner->cta_label }}</x-ui.button>
                        @endif
                    </article>
                @endforeach
            </x-ui.container>
        </section>
    @endif

    @if ($homepageSections->isNotEmpty())
        <section class="bg-zinc-50 dark:bg-zinc-900/50">
            <x-ui.container class="grid gap-6 py-12 md:grid-cols-3">
                @foreach ($homepageSections as $section)
                    <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                        <x-ui.badge>{{ str($section->type)->replace('_', ' ')->title() }}</x-ui.badge>

                        @if ($section->title)
                            <h2 class="mt-4 text-base font-semibold text-zinc-950 dark:text-white">{{ $section->title }}</h2>
                        @endif

                        @if ($section->subtitle)
                            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $section->subtitle }}</p>
                        @endif

                        @if (($section->content['description'] ?? null) !== null)
                            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $section->content['description'] }}</p>
                        @endif

                        @if (($section->content['cta_label'] ?? null) && ($section->content['cta_url'] ?? null))
                            <div class="mt-5">
                                <x-ui.button :href="$section->content['cta_url']" variant="secondary">{{ $section->content['cta_label'] }}</x-ui.button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </x-ui.container>
        </section>
    @else
        <section class="border-y border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-zinc-900/50">
            <x-ui.container class="grid gap-6 py-12 md:grid-cols-3">
                <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Everyday pairs') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Clean silhouettes and comfortable profiles for repeat wear across busy days.') }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Work-ready finish') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Structured materials, calm colorways, and clear details for professional routines.') }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Weekend rotation') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Versatile footwear stories that can expand into collections, campaigns, and launches.') }}</p>
                </article>
            </x-ui.container>
        </section>
    @endif

    @if ($serviceBenefits->isNotEmpty() || $testimonials->isNotEmpty() || $storeLocations->isNotEmpty())
        <section class="bg-white dark:bg-zinc-950">
            <x-ui.container class="grid gap-8 py-12 lg:grid-cols-3">
                @foreach ($serviceBenefits as $benefit)
                    <article class="rounded-lg border border-zinc-200 p-6 dark:border-white/10">
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $benefit->title }}</h2>
                        @if ($benefit->subtitle)
                            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $benefit->subtitle }}</p>
                        @endif
                    </article>
                @endforeach

                @foreach ($testimonials as $testimonial)
                    <article class="rounded-lg border border-zinc-200 p-6 dark:border-white/10">
                        <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $testimonial->quote }}</p>
                        <p class="mt-4 text-sm font-semibold text-zinc-950 dark:text-white">{{ $testimonial->name }}</p>
                    </article>
                @endforeach

                @foreach ($storeLocations as $location)
                    <article class="rounded-lg border border-zinc-200 p-6 dark:border-white/10">
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $location->name }}</h2>
                        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $location->line_one }}, {{ $location->city }}</p>
                    </article>
                @endforeach
            </x-ui.container>
        </section>
    @endif
</x-layouts.storefront>
