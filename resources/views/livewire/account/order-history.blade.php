<section class="w-full space-y-8">
    <x-ui.section-heading
        :title="__('Order history')"
        :description="__('Track order totals, payment states, shipping methods, and item snapshots linked to your account.')"
    >
        <x-slot:actions>
            <x-ui.button :href="route('shop')" variant="secondary">{{ __('Shop footwear') }}</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="subtle" wire:navigate>{{ __('Dashboard') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
        <label class="grid max-w-xs gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Status') }}
            <select wire:model.live="status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="">{{ __('All orders') }}</option>
                @foreach ($statuses as $orderStatus)
                    <option value="{{ $orderStatus->value }}">{{ str($orderStatus->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>
    </section>

    <section class="space-y-4">
        @forelse ($orders as $order)
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h2>
                            <x-ui.badge tone="teal">{{ str($order->status->value)->replace('_', ' ')->title() }}</x-ui.badge>
                            <x-ui.badge>{{ str($order->payment_status->value)->replace('_', ' ')->title() }}</x-ui.badge>
                        </div>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Placed') }}</dt>
                                <dd class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ $order->placed_at?->format('M j, Y') ?? $order->created_at?->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Items') }}</dt>
                                <dd class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ number_format($order->items->sum('quantity')) }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Shipping') }}</dt>
                                <dd class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ $order->shippingMethod?->name ?? __('Not assigned') }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</dt>
                                <dd class="mt-1 font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <x-ui.button :href="route('account.orders.show', ['order' => $order->order_number])" variant="secondary" wire:navigate>{{ __('View order') }}</x-ui.button>
                </div>
            </article>
        @empty
            <x-ui.empty-state
                :title="__('No orders found')"
                :description="__('Orders placed while signed in will appear here.')"
            >
                <x-slot:action>
                    <x-ui.button :href="route('shop')">{{ __('Shop footwear') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @endforelse

        @if ($orders->hasPages())
            <div>
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</section>
