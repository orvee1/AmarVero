<x-layouts::app :title="__('Account overview')">
    <div class="space-y-8">
        <x-ui.section-heading
            :title="__('Account overview')"
            :description="__('Review the activity currently linked to your Amarvero account.')"
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('account.orders')" wire:navigate>
                    {{ __('Orders') }}
                </x-ui.button>
                <x-ui.button variant="secondary" :href="route('account.addresses')" wire:navigate>
                    {{ __('Addresses') }}
                </x-ui.button>
                <x-ui.button variant="secondary" :href="route('profile.edit')" wire:navigate>
                    {{ __('Edit profile') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.section-heading>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($accountStats as $stat)
                <x-ui.stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :description="$stat['description']"
                    :tone="$stat['tone']"
                />
            @endforeach
        </div>

        <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Latest order') }}</h2>

            @if ($latestOrder)
                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Order number') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $latestOrder->order_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ str($latestOrder->status->value)->replace('_', ' ')->title() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $latestOrder->currency_code }} {{ number_format((float) $latestOrder->grand_total, 2) }}</dd>
                    </div>
                </dl>
                <div class="mt-5">
                    <x-ui.button :href="route('account.orders.show', ['order' => $latestOrder->order_number])" variant="secondary" wire:navigate>{{ __('View latest order') }}</x-ui.button>
                </div>
            @else
                <x-ui.empty-state
                    class="mt-5"
                    :title="__('No orders yet')"
                    :description="__('Orders connected to this account will appear here after checkout.')"
                />
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_22rem] xl:items-start">
            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Recent orders') }}</h2>
                    <x-ui.button :href="route('account.orders')" variant="subtle" size="sm" wire:navigate>{{ __('View all') }}</x-ui.button>
                </div>

                <div class="mt-5 grid gap-4">
                    @forelse ($recentOrders as $order)
                        <a href="{{ route('account.orders.show', ['order' => $order->order_number]) }}" wire:navigate class="grid gap-3 rounded-lg border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-white/5 sm:grid-cols-[1fr_auto]">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ str($order->status->value)->replace('_', ' ')->title() }} · {{ number_format($order->items->sum('quantity')) }} {{ __('items') }}</p>
                            </div>
                            <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Recent account orders will appear here.') }}</p>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Default shipping') }}</h2>
                    @if ($defaultShippingAddress)
                        <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            {{ $defaultShippingAddress->name }}<br>
                            {{ $defaultShippingAddress->line_one }}<br>
                            {{ $defaultShippingAddress->city }}, {{ $defaultShippingAddress->country_code }}
                        </p>
                    @else
                        <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('No default shipping address saved yet.') }}</p>
                    @endif
                    <div class="mt-5">
                        <x-ui.button :href="route('account.addresses')" variant="secondary" size="sm" wire:navigate>{{ __('Manage addresses') }}</x-ui.button>
                    </div>
                </section>

                <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Reviews') }}</h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __(':count reviews are waiting for moderation.', ['count' => number_format($pendingReviewCount)]) }}</p>
                    <div class="mt-5">
                        <x-ui.button :href="route('account.reviews')" variant="secondary" size="sm" wire:navigate>{{ __('Manage reviews') }}</x-ui.button>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>
