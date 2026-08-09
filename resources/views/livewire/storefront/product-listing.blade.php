<section class="bg-zinc-50 dark:bg-zinc-950">
    <x-ui.container class="space-y-8 py-10 lg:py-12">
        <header class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                    <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $pageTitle }}</span>
                </nav>
                <h1 class="mt-4 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ $pageTitle }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $pageDescription }}</p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300">
                {{ trans_choice(':count product found|:count products found', $products->total(), ['count' => number_format($products->total())]) }}
            </div>
        </header>

        @if (session('status'))
            <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Search') }}
                    <input type="search" wire:model.live.debounce.350ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Search products, brands, SKU') }}">
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort') }}
                    <select wire:model.live="sort" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Per page') }}
                    <select wire:model.live="perPage" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('View') }}
                    <select wire:model.live="viewMode" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="grid">{{ __('Grid') }}</option>
                        <option value="list">{{ __('List') }}</option>
                    </select>
                </label>
            </div>

            @if ($activeFilters !== [])
                <div class="flex flex-wrap gap-2">
                    @foreach ($activeFilters as $key => $label)
                        <button type="button" wire:click="removeFilter('{{ $key }}')" class="inline-flex min-h-8 items-center gap-2 rounded-full border border-zinc-200 px-3 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/5">
                            {{ $label }}
                            <span aria-hidden="true">x</span>
                        </button>
                    @endforeach

                    <x-ui.button type="button" size="sm" variant="subtle" wire:click="clearFilters">{{ __('Clear all') }}</x-ui.button>
                </div>
            @endif
        </section>

        <div class="grid gap-8 lg:grid-cols-[18rem_1fr]">
            <aside class="space-y-4">
                <details class="rounded-lg border border-zinc-200 bg-white p-4 lg:hidden dark:border-white/10 dark:bg-zinc-900">
                    <summary class="cursor-pointer text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Filters') }}</summary>
                    <div class="mt-4">
                        @include('livewire.storefront.partials.product-filters')
                    </div>
                </details>

                <div class="hidden rounded-lg border border-zinc-200 bg-white p-4 lg:block dark:border-white/10 dark:bg-zinc-900">
                    @include('livewire.storefront.partials.product-filters')
                </div>
            </aside>

            <section class="space-y-6">
                <div wire:loading.delay class="rounded-lg border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-600 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-300">
                    {{ __('Updating products...') }}
                </div>

                @if ($products->isNotEmpty())
                    <div class="{{ $viewMode === 'list' ? 'grid gap-4' : 'grid gap-5 sm:grid-cols-2 xl:grid-cols-3' }}">
                        @foreach ($products as $product)
                            <x-storefront.product-card :product="$product" :catalog="$catalog" :class="$viewMode === 'list' ? 'sm:grid sm:grid-cols-[14rem_1fr]' : ''" />
                        @endforeach
                    </div>

                    <div>
                        {{ $products->links() }}
                    </div>
                @else
                    <x-ui.empty-state
                        :title="__('No products found')"
                        :description="__('Adjust your filters or search terms to find more products.')"
                    >
                        <x-slot:action>
                            <x-ui.button type="button" wire:click="clearFilters">{{ __('Clear filters') }}</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @endif
            </section>
        </div>
    </x-ui.container>
</section>
