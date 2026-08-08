<section class="bg-zinc-50 dark:bg-zinc-950">
    <x-ui.container class="space-y-8 py-10 lg:py-12">
        <header class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                    <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
                    <span aria-hidden="true">/</span>
                    <span>{{ __('Cart') }}</span>
                </nav>
                <h1 class="mt-4 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ __('Shopping cart') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review selected footwear before checkout details are collected.') }}</p>
            </div>

            @if ($summary['item_count'] > 0)
                <x-ui.button type="button" variant="subtle" wire:click="clear">{{ __('Clear cart') }}</x-ui.button>
            @endif
        </header>

        @if (session('status'))
            <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
                {{ session('status') }}
            </div>
        @endif

        @error('cart')
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">
                {{ $message }}
            </div>
        @enderror

        @if ($summary['lines'] === [])
            <x-ui.empty-state
                :title="__('Your cart is empty')"
                :description="__('Add a pair from the storefront to start a cart.')"
            >
                <x-slot:action>
                    <x-ui.button :href="route('shop')">{{ __('Shop footwear') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">
                <section class="space-y-4">
                    @foreach ($summary['lines'] as $line)
                        @php
                            $item = $line['item'];
                            $options = is_array($item->options) ? $item->options : [];
                            $productSlug = $options['product_slug'] ?? $item->product->slug;
                            $productName = $options['product_name'] ?? $item->product->name;
                            $imageUrl = $options['image_url'] ?? null;
                        @endphp

                        <article class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-[7rem_1fr]">
                            <a class="overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800" href="{{ route('products.show', ['product' => $productSlug]) }}">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $productName }}" width="280" height="350" class="aspect-[4/5] h-full w-full object-cover">
                                @else
                                    <div class="flex aspect-[4/5] h-full items-center justify-center px-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Image coming soon') }}</div>
                                @endif
                            </a>

                            <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ $item->product->brand?->name ?? __('Amarvero') }}</p>
                                    <h2 class="mt-1 text-base font-semibold text-zinc-950 dark:text-white">
                                        <a href="{{ route('products.show', ['product' => $productSlug]) }}">{{ $productName }}</a>
                                    </h2>

                                    @if (($options['variant_label'] ?? null) !== null)
                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $options['variant_label'] }}</p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        @foreach (($options['attributes'] ?? []) as $attribute)
                                            @if (is_array($attribute))
                                                <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-white/10">{{ $attribute['attribute'] ?? '' }}: {{ $attribute['value'] ?? '' }}</span>
                                            @endif
                                        @endforeach
                                    </div>

                                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('SKU: :sku', ['sku' => $options['sku'] ?? $item->productVariant->sku]) }}</p>
                                </div>

                                <div class="grid gap-4 md:min-w-52 md:justify-items-end">
                                    <div class="text-left md:text-right">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Unit price') }}</p>
                                        <p class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($line['unit_price'], 2) }}</p>
                                    </div>

                                    <div class="flex items-center rounded-lg border border-zinc-300 dark:border-white/15">
                                        <button type="button" wire:click="decrement({{ $item->id }})" class="flex size-10 items-center justify-center text-lg font-semibold text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5" aria-label="{{ __('Decrease quantity') }}">-</button>
                                        <input type="number" min="1" max="{{ \App\Support\Cart\CartManager::MAX_QUANTITY_PER_ITEM }}" value="{{ $item->quantity }}" wire:change="updateQuantity({{ $item->id }}, $event.target.value)" class="h-10 w-16 border-x border-zinc-300 bg-white text-center text-sm font-semibold text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                        <button type="button" wire:click="increment({{ $item->id }})" class="flex size-10 items-center justify-center text-lg font-semibold text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5" aria-label="{{ __('Increase quantity') }}">+</button>
                                    </div>

                                    <div class="text-left md:text-right">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Line total') }}</p>
                                        <p class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($line['line_total'], 2) }}</p>
                                    </div>

                                    <x-ui.button type="button" variant="subtle" size="sm" wire:click="remove({{ $item->id }})">{{ __('Remove') }}</x-ui.button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Cart summary') }}</h2>
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Items') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">{{ number_format($summary['item_count']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-white/10">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Subtotal') }}</dt>
                            <dd class="text-xl font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($summary['subtotal'], 2) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Shipping, discounts, and payment are calculated after checkout information is entered.') }}</p>

                    <div class="mt-6 grid gap-3">
                        <x-ui.button :href="route('checkout')" class="w-full">{{ __('Checkout') }}</x-ui.button>
                        <x-ui.button :href="route('shop')" variant="secondary" class="w-full">{{ __('Continue shopping') }}</x-ui.button>
                    </div>
                </aside>
            </div>
        @endif
    </x-ui.container>
</section>
