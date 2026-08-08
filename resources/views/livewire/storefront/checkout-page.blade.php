<section class="bg-zinc-50 dark:bg-zinc-950">
    <x-ui.container class="space-y-8 py-10 lg:py-12">
        <header>
            <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('cart') }}">{{ __('Cart') }}</a>
                <span aria-hidden="true">/</span>
                <span>{{ __('Checkout') }}</span>
            </nav>
            <h1 class="mt-4 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ __('Checkout') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Confirm delivery details, apply eligible offers, and place the order with server-verified totals.') }}</p>
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

        @if ($preview['lines'] === [])
            <x-ui.empty-state
                :title="__('Your cart is empty')"
                :description="__('Add an item before checkout can begin.')"
            >
                <x-slot:action>
                    <x-ui.button :href="route('shop')">{{ __('Shop footwear') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <form wire:submit="placeOrder" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
                <div class="space-y-6">
                    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Contact') }}</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Name') }}
                                <input wire:model.blur="form.customer_name" type="text" autocomplete="name" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.customer_name" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Email') }}
                                <input wire:model.blur="form.email" type="email" autocomplete="email" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.email" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 sm:col-span-2">
                                {{ __('Phone') }}
                                <input wire:model.blur="form.phone" type="tel" autocomplete="tel" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.phone" />
                            </label>
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Delivery address') }}</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 sm:col-span-2">
                                {{ __('Address line 1') }}
                                <input wire:model.blur="form.line_one" type="text" autocomplete="address-line1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.line_one" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 sm:col-span-2">
                                {{ __('Address line 2') }}
                                <input wire:model.blur="form.line_two" type="text" autocomplete="address-line2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.line_two" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Area') }}
                                <input wire:model.blur="form.area" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.area" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('City') }}
                                <input wire:model.blur="form.city" type="text" autocomplete="address-level2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.city" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Region') }}
                                <input wire:model.live="form.region" type="text" autocomplete="address-level1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.region" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Postal code') }}
                                <input wire:model.blur="form.postal_code" type="text" autocomplete="postal-code" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.postal_code" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Country') }}
                                <input wire:model.live="form.country_code" type="text" maxlength="2" autocomplete="country" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm uppercase text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="form.country_code" />
                            </label>
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Shipping') }}</h2>
                        <div class="mt-5 grid gap-3">
                            @forelse ($shippingRates as $rate)
                                <label class="grid cursor-pointer gap-2 rounded-lg border border-zinc-200 p-4 text-sm dark:border-white/10">
                                    <span class="flex items-start justify-between gap-4">
                                        <span class="flex items-start gap-3">
                                            <input wire:model.live="shippingMethodId" type="radio" value="{{ $rate['method']->id }}" class="mt-1">
                                            <span>
                                                <span class="block font-semibold text-zinc-950 dark:text-white">{{ $rate['method']->name }}</span>
                                                @if ($rate['method']->estimated_days_min || $rate['method']->estimated_days_max)
                                                    <span class="mt-1 block text-zinc-500 dark:text-zinc-400">{{ __('Estimated :min-:max days', ['min' => $rate['method']->estimated_days_min ?? 1, 'max' => $rate['method']->estimated_days_max ?? $rate['method']->estimated_days_min]) }}</span>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="font-semibold text-zinc-950 dark:text-white">
                                            @if ($rate['rate'] <= 0)
                                                {{ __('Free') }}
                                            @else
                                                BDT {{ number_format($rate['rate'], 2) }}
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">{{ __('No shipping methods are available for this address yet.') }}</p>
                            @endforelse
                            <x-ui.input-error for="shippingMethodId" />
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Payment') }}</h2>
                        <div class="mt-5 grid gap-3">
                            @foreach ($paymentMethods as $value => $label)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-4 text-sm dark:border-white/10">
                                    <input wire:model.live="paymentMethod" type="radio" value="{{ $value }}" class="mt-1">
                                    <span>
                                        <span class="block font-semibold text-zinc-950 dark:text-white">{{ $label }}</span>
                                        <span class="mt-1 block text-zinc-500 dark:text-zinc-400">{{ __('Payment stays pending until operations confirms it.') }}</span>
                                    </span>
                                </label>
                            @endforeach
                            <x-ui.input-error for="paymentMethod" />
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Order note') }}
                            <textarea wire:model.blur="form.customer_note" rows="4" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                            <x-ui.input-error for="form.customer_note" />
                        </label>
                    </section>
                </div>

                <aside class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Order summary') }}</h2>

                    <div class="grid gap-4">
                        @foreach ($preview['lines'] as $line)
                            @php
                                $item = $line['item'];
                                $options = is_array($item->options) ? $item->options : [];
                                $productName = $options['product_name'] ?? $item->product->name;
                            @endphp
                            <div class="flex items-start justify-between gap-4 text-sm">
                                <div>
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $productName }}</p>
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Qty :quantity', ['quantity' => $line['quantity']]) }}</p>
                                </div>
                                <p class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($line['line_total'], 2) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Coupon') }}
                            <span class="flex gap-2">
                                <input wire:model.blur="couponCode" type="text" class="min-h-10 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 text-sm uppercase text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.button type="button" variant="secondary" wire:click="applyCoupon">{{ __('Apply') }}</x-ui.button>
                            </span>
                            <x-ui.input-error for="couponCode" />
                        </label>

                        @if ($preview['coupon_result']['coupon'])
                            <div class="mt-3 flex items-center justify-between gap-3 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900 dark:bg-teal-400/10 dark:text-teal-100">
                                <span>{{ $preview['coupon_result']['message'] }}</span>
                                <button type="button" wire:click="removeCoupon" class="font-semibold hover:underline">{{ __('Remove') }}</button>
                            </div>
                        @elseif ($couponMessage)
                            <p class="mt-3 rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-700 dark:bg-white/10 dark:text-zinc-200">{{ $couponMessage }}</p>
                        @endif
                    </div>

                    <dl class="grid gap-3 border-t border-zinc-200 pt-5 text-sm dark:border-white/10">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Subtotal') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($preview['subtotal'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Discount') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">- BDT {{ number_format($preview['discount_total'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Shipping') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($preview['shipping_total'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-white/10">
                            <dt class="text-zinc-950 dark:text-white">{{ __('Total') }}</dt>
                            <dd class="text-xl font-semibold text-zinc-950 dark:text-white">BDT {{ number_format($preview['grand_total'], 2) }}</dd>
                        </div>
                    </dl>

                    <x-ui.button type="submit" class="w-full" :disabled="$shippingRates->isEmpty()">{{ __('Place order') }}</x-ui.button>
                    <x-ui.button :href="route('cart')" variant="subtle" class="w-full">{{ __('Return to cart') }}</x-ui.button>
                </aside>
            </form>
        @endif
    </x-ui.container>
</section>
