<section class="w-full space-y-8">
    <x-ui.section-heading
        :title="__('Order :number', ['number' => $order->order_number])"
        :description="__('Review the saved order snapshot and fulfillment timeline for this purchase.')"
    >
        <x-slot:actions>
            <x-ui.button :href="route('account.orders')" variant="secondary" wire:navigate>{{ __('All orders') }}</x-ui.button>
            <x-ui.button :href="route('account.reviews')" variant="subtle" wire:navigate>{{ __('Review products') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card :label="__('Status')" :value="str($order->status->value)->replace('_', ' ')->title()" :description="__('Current order state.')" tone="teal" />
        <x-ui.stat-card :label="__('Payment')" :value="str($order->payment_status->value)->replace('_', ' ')->title()" :description="__('Payment lifecycle state.')" />
        <x-ui.stat-card :label="__('Items')" :value="number_format($order->items->sum('quantity'))" :description="__('Units in this order.')" />
        <x-ui.stat-card :label="__('Total')" :value="$order->currency_code.' '.number_format((float) $order->grand_total, 2)" :description="__('Order grand total.')" tone="amber" />
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem] xl:items-start">
        <section class="space-y-4">
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Items') }}</h2>
                <div class="mt-5 divide-y divide-zinc-200 dark:divide-white/10">
                    @foreach ($order->items as $item)
                        <div class="grid gap-3 py-4 first:pt-0 last:pb-0 sm:grid-cols-[1fr_auto]">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $item->product_name }}</p>
                                @if ($item->variant_name)
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->variant_name }}</p>
                                @endif
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('SKU: :sku', ['sku' => $item->sku ?: __('N/A')]) }}</p>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Qty :quantity', ['quantity' => $item->quantity]) }}</p>
                                <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $order->currency_code }} {{ number_format((float) $item->line_total, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Timeline') }}</h2>
                <div class="mt-5 grid gap-4">
                    @forelse ($order->statusEvents->sortByDesc('created_at') as $event)
                        <div class="rounded-lg bg-zinc-50 p-4 text-sm dark:bg-white/5">
                            <p class="font-semibold text-zinc-950 dark:text-white">{{ str($event->to_status->value)->replace('_', ' ')->title() }}</p>
                            @if ($event->note)
                                <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $event->note }}</p>
                            @endif
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $event->created_at?->format('M j, Y g:i A') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('No status events have been recorded yet.') }}</p>
                    @endforelse
                </div>
            </article>
        </section>

        <aside class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div>
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Summary') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Discount') }}</dt>
                        <dd class="font-semibold text-zinc-950 dark:text-white">- {{ $order->currency_code }} {{ number_format((float) $order->discount_total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Shipping') }}</dt>
                        <dd class="font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->shipping_total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-white/10">
                        <dt class="text-zinc-950 dark:text-white">{{ __('Grand total') }}</dt>
                        <dd class="font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Payment') }}</h2>
                @forelse ($order->payments as $payment)
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ str($payment->method->value)->replace('_', ' ')->title() }} · {{ str($payment->status->value)->replace('_', ' ')->title() }}</p>
                    <p class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $payment->amount, 2) }}</p>
                @empty
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('No payment record is attached yet.') }}</p>
                @endforelse
            </div>

            <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Shipping address') }}</h2>
                @php($shippingAddress = $order->addresses->firstWhere('type', \App\Enums\AddressType::Shipping))
                @if ($shippingAddress)
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ $shippingAddress->name }}<br>
                        {{ $shippingAddress->line_one }}@if ($shippingAddress->line_two), {{ $shippingAddress->line_two }}@endif<br>
                        {{ $shippingAddress->city }}@if ($shippingAddress->region), {{ $shippingAddress->region }}@endif<br>
                        {{ $shippingAddress->country_code }} · {{ $shippingAddress->phone }}
                    </p>
                @else
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('No shipping address snapshot found.') }}</p>
                @endif
            </div>
        </aside>
    </div>
</section>
