<section class="bg-zinc-50 dark:bg-zinc-950">
    <x-ui.container class="space-y-8 py-10 lg:py-12">
        <header>
            <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span>{{ __('Wishlist') }}</span>
            </nav>
            <h1 class="mt-4 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ __('Wishlist') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Saved products stay with your account across devices.') }}</p>
        </header>

        @if (session('status'))
            <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
                {{ session('status') }}
            </div>
        @endif

        @error('wishlist')
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">
                {{ $message }}
            </div>
        @enderror

        @if (! $wishlist || $wishlist->items->isEmpty())
            <x-ui.empty-state
                :title="__('Your wishlist is empty')"
                :description="__('Save products while browsing to compare them later.')"
            >
                <x-slot:action>
                    <x-ui.button :href="route('shop')">{{ __('Shop footwear') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($wishlist->items as $wishlistItem)
                    @php
                        $product = $wishlistItem->product;
                        $variant = $wishlistItem->productVariant;
                        $image = $variant?->images->first() ?: $catalog->primaryImage($product);
                        $imageUrl = $image ? $catalog->mediaUrl($image->path, $image->disk) : null;
                        $price = $variant?->price_override === null ? $catalog->effectivePrice($product) : (float) $variant->price_override;
                        $activeVariants = $product->variants->where('is_active', true);
                        $cartVariant = $variant ?: ($activeVariants->count() === 1 ? $activeVariants->first() : null);
                        $canMoveToCart = $cartVariant && ($cartVariant->availableQuantity() > 0 || $cartVariant->allow_backorder || $product->allow_backorder);
                    @endphp

                    <article class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="block overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" width="640" height="800" class="aspect-[4/5] h-full w-full object-cover">
                            @else
                                <div class="flex aspect-[4/5] h-full items-center justify-center px-6 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Image coming soon') }}</div>
                            @endif
                        </a>

                        <div class="space-y-4 p-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ $product->brand?->name ?? __('Amarvero') }}</p>
                                <h2 class="mt-1 text-base font-semibold text-zinc-950 dark:text-white">
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}">{{ $product->name }}</a>
                                </h2>
                                @if ($variant)
                                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $variant->option_label ?: $variant->sku }}</p>
                                @endif
                            </div>

                            @if ($price !== null)
                                <p class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($price, 2) }}</p>
                            @endif

                            <div class="grid gap-2">
                                @if ($canMoveToCart)
                                    <x-ui.button type="button" wire:click="moveToCart({{ $wishlistItem->id }})">{{ __('Move to cart') }}</x-ui.button>
                                @else
                                    <x-ui.button :href="route('products.show', ['product' => $product->slug])" variant="secondary">{{ __('Choose options') }}</x-ui.button>
                                @endif

                                <x-ui.button type="button" variant="subtle" wire:click="remove({{ $wishlistItem->id }})">{{ __('Remove') }}</x-ui.button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-ui.container>
</section>
