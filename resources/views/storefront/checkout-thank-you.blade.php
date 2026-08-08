<x-layouts.storefront :title="__('Order confirmed')">
    <section class="bg-zinc-50 dark:bg-zinc-950">
        <x-ui.container class="space-y-8 py-10 lg:py-12">
            <header class="max-w-3xl">
                <x-ui.badge tone="teal">{{ __('Order received') }}</x-ui.badge>
                <h1 class="mt-4 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ __('Thank you for your order') }}</h1>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('We created order :number and saved the order, payment, address, and item snapshots for operations review.', ['number' => $order->order_number]) }}</p>
            </header>

            @if (session('status'))
                <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Items') }}</h2>
                    <div class="mt-5 grid gap-4">
                        @foreach ($order->items as $item)
                            <div class="flex items-start justify-between gap-4 text-sm">
                                <div>
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $item->product_name }}</p>
                                    @if ($item->variant_name)
                                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $item->variant_name }}</p>
                                    @endif
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Qty :quantity', ['quantity' => $item->quantity]) }}</p>
                                </div>
                                <p class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $item->line_total, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <aside class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Order number') }}</p>
                        <p class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</p>
                    </div>

                    <dl class="grid gap-3 border-t border-zinc-200 pt-5 text-sm dark:border-white/10">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Subtotal') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $order->subtotal, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Discount') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">- BDT {{ number_format((float) $order->discount_total, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Shipping') }}</dt>
                            <dd class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $order->shipping_total, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-white/10">
                            <dt class="text-zinc-950 dark:text-white">{{ __('Total') }}</dt>
                            <dd class="text-xl font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $order->grand_total, 2) }}</dd>
                        </div>
                    </dl>

                    <div class="border-t border-zinc-200 pt-5 text-sm dark:border-white/10">
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Payment') }}</p>
                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ __(str_replace('_', ' ', (string) $order->payments->first()?->method?->value)) }}</p>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Status: :status', ['status' => str_replace('_', ' ', $order->payment_status->value)]) }}</p>
                    </div>

                    <x-ui.button :href="route('shop')" class="w-full">{{ __('Continue shopping') }}</x-ui.button>
                </aside>
            </div>
        </x-ui.container>
    </section>
</x-layouts.storefront>
