@props([
    'product',
    'catalog',
])

@php
    $primaryImage = $catalog->primaryImage($product);
    $secondaryImage = $catalog->secondaryImage($product);
    $primaryUrl = $primaryImage ? $catalog->mediaUrl($primaryImage->path, $primaryImage->disk) : null;
    $secondaryUrl = $secondaryImage ? $catalog->mediaUrl($secondaryImage->path, $secondaryImage->disk) : null;
    $price = $catalog->effectivePrice($product);
    $discountPercent = $catalog->discountPercent($product);
    $colorValues = $catalog->colorValues($product);
@endphp

<article {{ $attributes->class('group overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-zinc-900') }}>
    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">
        <div class="relative aspect-[4/5] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($primaryUrl)
                <img
                    src="{{ $primaryUrl }}"
                    alt="{{ $primaryImage->alt_text ?: $product->name }}"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    loading="lazy"
                    width="640"
                    height="800"
                >
            @else
                <div class="flex h-full items-center justify-center px-6 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ __('Image coming soon') }}
                </div>
            @endif

            @if ($secondaryUrl)
                <img
                    src="{{ $secondaryUrl }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-300 group-hover:opacity-100"
                    loading="lazy"
                    width="640"
                    height="800"
                >
            @endif

            <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                @if ($catalog->isOnSale($product))
                    <x-ui.badge tone="rose">{{ $discountPercent ? __('-:percent%', ['percent' => $discountPercent]) : __('Sale') }}</x-ui.badge>
                @endif
                @if ($product->is_new_arrival)
                    <x-ui.badge tone="teal">{{ __('New') }}</x-ui.badge>
                @endif
                @if ($product->is_featured)
                    <x-ui.badge tone="amber">{{ __('Featured') }}</x-ui.badge>
                @endif
            </div>
        </div>

        <div class="space-y-3 p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">{{ $product->brand?->name ?? __('Amarvero') }}</p>
                <h2 class="mt-1 line-clamp-2 text-base font-semibold text-zinc-950 dark:text-white">{{ $product->name }}</h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($price !== null)
                    <span class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($price, 2) }}</span>
                @endif

                @if ($catalog->isOnSale($product) && $product->regular_price)
                    <span class="text-sm text-zinc-500 line-through dark:text-zinc-400">BDT {{ number_format((float) $product->regular_price, 2) }}</span>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3">
                <x-ui.badge :tone="$catalog->stockTone($product)">{{ $catalog->stockLabel($product) }}</x-ui.badge>

                @if ($colorValues->isNotEmpty())
                    <div class="flex -space-x-1" aria-label="{{ __('Available colors') }}">
                        @foreach ($colorValues->take(5) as $colorValue)
                            <span
                                class="size-5 rounded-full border border-white shadow ring-1 ring-zinc-200 dark:border-zinc-900 dark:ring-white/20"
                                title="{{ $colorValue->display_value ?: $colorValue->value }}"
                                style="background-color: {{ $colorValue->color_hex ?: '#d4d4d8' }}"
                            ></span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </a>

    <div class="border-t border-zinc-200 dark:border-white/10">
        <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="block px-4 py-3 text-center text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5">
            {{ __('View details') }}
        </a>
    </div>
</article>
