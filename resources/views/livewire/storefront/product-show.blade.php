@php
    $basePrice = $catalog->effectivePrice($product);
    $price = $selectedVariant?->price_override === null ? $basePrice : (float) $selectedVariant->price_override;
    $available = $selectedVariant ? $selectedVariant->availableQuantity() : $catalog->availableQuantity($product);
    $lowThreshold = $selectedVariant?->low_stock_threshold;
    $stockTone = $available <= 0 ? 'rose' : ($lowThreshold !== null && $available <= (int) $lowThreshold ? 'amber' : 'teal');
    $stockLabel = $available <= 0 ? __('Out of stock') : ($stockTone === 'amber' ? __('Low stock') : __('In stock'));
    $discountPercent = $catalog->discountPercent($product);
    $reviewCount = (int) ($product->approved_reviews_count ?? 0);
    $averageRating = (float) ($product->approved_reviews_avg_rating ?? 0);
    $canPurchase = $selectedVariant && ($available > 0 || $selectedVariant->allow_backorder || $product->allow_backorder);
@endphp

<section class="bg-zinc-50 dark:bg-zinc-950">
    <x-ui.container class="space-y-10 py-10 lg:py-12">
        <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
            <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('shop') }}">{{ __('Shop') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $product->name }}</span>
        </nav>

        @if (session('status'))
            <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)] lg:items-start">
            <section class="grid gap-4">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900">
                    @if ($activeImageUrl)
                        <img
                            src="{{ $activeImageUrl }}"
                            alt="{{ $product->name }}"
                            width="960"
                            height="1200"
                            class="aspect-[4/5] h-full w-full object-cover"
                        >
                    @else
                        <div class="flex aspect-[4/5] items-center justify-center px-6 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            {{ __('Image coming soon') }}
                        </div>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                        @foreach ($product->images->take(6) as $image)
                            @php($imageUrl = $catalog->mediaUrl($image->path, $image->disk))
                            @if ($imageUrl)
                                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900">
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $image->alt_text ?: $product->name }}"
                                        width="240"
                                        height="300"
                                        class="aspect-[4/5] h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-6">
                <header class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($product->brand)
                            <a class="text-sm font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-200 dark:hover:text-teal-100" href="{{ route('brands.show', ['slug' => $product->brand->slug]) }}">
                                {{ $product->brand->name }}
                            </a>
                        @else
                            <span class="text-sm font-semibold text-teal-700 dark:text-teal-200">{{ __('Amarvero') }}</span>
                        @endif

                        @if ($catalog->isOnSale($product))
                            <x-ui.badge tone="rose">{{ $discountPercent ? __('-:percent%', ['percent' => $discountPercent]) : __('Sale') }}</x-ui.badge>
                        @endif
                        @if ($product->is_new_arrival)
                            <x-ui.badge tone="teal">{{ __('New arrival') }}</x-ui.badge>
                        @endif
                        @if ($product->is_best_seller)
                            <x-ui.badge tone="amber">{{ __('Best seller') }}</x-ui.badge>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ $product->name }}</h1>
                        @if ($product->short_description)
                            <p class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-300">{{ $product->short_description }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        @if ($price !== null)
                            <span class="text-3xl font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($price, 2) }}</span>
                        @endif

                        @if ($catalog->isOnSale($product) && $product->regular_price)
                            <span class="pb-1 text-base text-zinc-500 line-through dark:text-zinc-400">BDT {{ number_format((float) $product->regular_price, 2) }}</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.badge :tone="$stockTone">{{ $stockLabel }}</x-ui.badge>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __(':count available', ['count' => number_format($available)]) }}</span>
                        @if ($selectedVariant)
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('SKU: :sku', ['sku' => $selectedVariant->sku]) }}</span>
                        @elseif ($product->base_sku)
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('SKU: :sku', ['sku' => $product->base_sku]) }}</span>
                        @endif
                    </div>

                    @if ($reviewCount > 0)
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Rated :rating out of 5 from :count reviews', ['rating' => number_format($averageRating, 1), 'count' => number_format($reviewCount)]) }}
                        </p>
                    @endif
                </header>

                @if ($optionGroups->isNotEmpty())
                    <section class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        @foreach ($optionGroups as $attributeValues)
                            @php($attribute = $attributeValues->first()?->productAttribute)
                            @if ($attribute)
                                <fieldset>
                                    <legend class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $attribute->name }}</legend>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($attributeValues as $attributeValue)
                                            @php($optionId = 'option-'.$attribute->id.'-'.$attributeValue->id)
                                            <label for="{{ $optionId }}" class="cursor-pointer">
                                                <input
                                                    id="{{ $optionId }}"
                                                    type="radio"
                                                    value="{{ $attributeValue->id }}"
                                                    wire:model.live="selectedOptions.{{ $attribute->id }}"
                                                    class="peer sr-only"
                                                    @checked((string) ($selectedOptions[$attribute->id] ?? '') === (string) $attributeValue->id)
                                                >
                                                <span class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 text-sm font-semibold text-zinc-700 transition peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-900 dark:border-white/15 dark:bg-zinc-950 dark:text-zinc-200 dark:peer-checked:border-teal-300 dark:peer-checked:bg-teal-400/10 dark:peer-checked:text-teal-100">
                                                    @if ($attributeValue->color_hex)
                                                        <span class="size-4 rounded-full border border-white shadow ring-1 ring-zinc-200 dark:border-zinc-900 dark:ring-white/20" style="background-color: {{ $attributeValue->color_hex }}"></span>
                                                    @endif
                                                    {{ $attributeValue->display_value ?: $attributeValue->value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif
                        @endforeach

                        @if ($selectedVariant)
                            <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-white/5 dark:text-zinc-300">
                                {{ __('Selected: :variant', ['variant' => $selectedVariant->option_label ?: $selectedVariant->sku]) }}
                            </div>
                        @else
                            <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-100">
                                {{ __('This option combination is unavailable.') }}
                            </div>
                        @endif
                    </section>
                @endif

                <section class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Purchase') }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Price shown before shipping and discounts.') }}</p>
                        </div>

                        <div class="flex items-center rounded-lg border border-zinc-300 dark:border-white/15">
                            <button type="button" wire:click="decrementQuantity" class="flex size-10 items-center justify-center text-lg font-semibold text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5" aria-label="{{ __('Decrease quantity') }}">-</button>
                            <input wire:model.live.debounce.250ms="quantity" type="number" min="1" max="{{ \App\Support\Cart\CartManager::MAX_QUANTITY_PER_ITEM }}" class="h-10 w-16 border-x border-zinc-300 bg-white text-center text-sm font-semibold text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <button type="button" wire:click="incrementQuantity" class="flex size-10 items-center justify-center text-lg font-semibold text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5" aria-label="{{ __('Increase quantity') }}">+</button>
                        </div>
                    </div>

                    @error('cart')
                        <p class="text-sm font-medium text-rose-700 dark:text-rose-200">{{ $message }}</p>
                    @enderror

                    @error('wishlist')
                        <p class="text-sm font-medium text-rose-700 dark:text-rose-200">{{ $message }}</p>
                    @enderror

                    <div class="grid gap-3 sm:grid-cols-2">
                        @if ($canPurchase)
                            <x-ui.button type="button" wire:click="addToCart">{{ __('Add to cart') }}</x-ui.button>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
                                {{ __('This option is unavailable for cart purchase.') }}
                            </div>
                        @endif

                        @auth
                            <x-ui.button type="button" variant="secondary" wire:click="addToWishlist">{{ __('Save to wishlist') }}</x-ui.button>
                        @else
                            <x-ui.button :href="route('login')" variant="secondary">{{ __('Sign in to save') }}</x-ui.button>
                        @endauth
                    </div>
                </section>

                <section class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-5 text-sm text-zinc-600 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300 sm:grid-cols-3">
                    <div>
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Delivery') }}</p>
                        <p class="mt-1">{{ __('Shipping method and fee appear at checkout.') }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Exchange') }}</p>
                        <p class="mt-1">{{ __('Eligible pairs can be exchanged after review.') }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Payment') }}</p>
                        <p class="mt-1">{{ __('Payment options are confirmed during checkout.') }}</p>
                    </div>
                </section>
            </section>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <section class="space-y-6">
                <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Product details') }}</h2>

                    @if ($product->description)
                        <div class="mt-4 space-y-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    @endif

                    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                        @if ($product->material)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Material') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-200">{{ $product->material }}</dd>
                            </div>
                        @endif
                        @if ($product->gender)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-200">{{ str($product->gender)->title() }}</dd>
                            </div>
                        @endif
                        @foreach ($product->attributeValues as $attributeValue)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ $attributeValue->productAttribute?->name }}</dt>
                                <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-200">{{ $attributeValue->display_value ?: $attributeValue->value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($product->care_instructions)
                        <div class="mt-6 border-t border-zinc-200 pt-5 dark:border-white/10">
                            <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Care') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $product->care_instructions }}</p>
                        </div>
                    @endif
                </article>

                @if ($product->sizeGuides->where('is_active', true)->isNotEmpty())
                    <article class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Size guide') }}</h2>

                        <div class="mt-5 grid gap-5">
                            @foreach ($product->sizeGuides->where('is_active', true) as $sizeGuide)
                                <section class="rounded-lg bg-zinc-50 p-4 dark:bg-white/5">
                                    <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $sizeGuide->name }}</h3>
                                    @if ($sizeGuide->content)
                                        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $sizeGuide->content }}</p>
                                    @endif

                                    @if (is_array($sizeGuide->measurements) && $sizeGuide->measurements !== [])
                                        <dl class="mt-4 grid gap-2">
                                            @foreach ($sizeGuide->measurements as $row)
                                                @if (is_array($row) && filled($row['label'] ?? null))
                                                    <div class="grid gap-1 rounded-md bg-white px-3 py-2 text-sm dark:bg-zinc-950 sm:grid-cols-[8rem_1fr]">
                                                        <dt class="font-semibold text-zinc-950 dark:text-white">{{ $row['label'] }}</dt>
                                                        <dd class="text-zinc-600 dark:text-zinc-300">{{ $row['measurement'] ?? '' }}</dd>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </dl>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </article>
                @endif
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Reviews') }}</h2>

                    @if ($reviewCount > 0)
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Average :rating from :count reviews', ['rating' => number_format($averageRating, 1), 'count' => number_format($reviewCount)]) }}</p>
                        <div class="mt-5 grid gap-2">
                            @foreach ($reviewRows as $reviewRow)
                                <div class="grid grid-cols-[3rem_1fr_3rem] items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span>{{ $reviewRow['rating'] }}/5</span>
                                    <span class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                                        <span class="block h-full rounded-full bg-teal-600 dark:bg-teal-300" style="width: {{ $reviewRow['percent'] }}%"></span>
                                    </span>
                                    <span class="text-right">{{ $reviewRow['count'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 grid gap-4">
                            @foreach ($product->reviews->take(3) as $review)
                                <article class="border-t border-zinc-200 pt-4 dark:border-white/10">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $review->title ?: __('Customer review') }}</h3>
                                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $review->rating }}/5</span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $review->body }}</p>
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $review->user?->name ?? __('Verified customer') }}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('No approved reviews yet.') }}</p>
                    @endif
                </section>

                @if ($product->categories->isNotEmpty() || $product->collections->isNotEmpty())
                    <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Explore') }}</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($product->categories as $category)
                                <a class="rounded-full border border-zinc-200 px-3 py-1 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/5" href="{{ route('categories.show', ['slug' => $category->slug]) }}">{{ $category->name }}</a>
                            @endforeach
                            @foreach ($product->collections as $collection)
                                <a class="rounded-full border border-zinc-200 px-3 py-1 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/5" href="{{ route('collections.show', ['slug' => $collection->slug]) }}">{{ $collection->name }}</a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="space-y-5">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Related products') }}</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('More published products from nearby categories or the same brand.') }}</p>
                    </div>
                    <x-ui.button variant="secondary" :href="route('shop')">{{ __('Shop all') }}</x-ui.button>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $relatedProduct)
                        <x-storefront.product-card :product="$relatedProduct" :catalog="$catalog" />
                    @endforeach
                </div>
            </section>
        @endif
    </x-ui.container>
</section>
