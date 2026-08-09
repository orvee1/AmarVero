<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Operations')"
        :title="__('Customers')"
        :description="__('Review customer profiles, order value, saved addresses, wishlists, and review history from one support workspace.')"
    />

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search customers') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or email') }}">
        </label>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.9fr)]">
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            @if ($customers->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                        <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Customer') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Orders') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Account') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                            @foreach ($customers as $customer)
                                <tr wire:key="customer-{{ $customer->id }}" class="@if ($selectedCustomer?->id === $customer->id) bg-teal-50/70 dark:bg-teal-400/10 @endif">
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $customer->name }}</div>
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $customer->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                        <span class="block">{{ trans_choice(':count order|:count orders', $customer->orders_count, ['count' => number_format($customer->orders_count)]) }}</span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">BDT {{ number_format((float) ($customer->orders_sum_grand_total ?? 0), 2) }}</span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                        <span class="block">{{ trans_choice(':count address|:count addresses', $customer->addresses_count, ['count' => number_format($customer->addresses_count)]) }}</span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count review|:count reviews', $customer->product_reviews_count, ['count' => number_format($customer->product_reviews_count)]) }}</span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex justify-end">
                                            <x-ui.button size="sm" variant="secondary" wire:click="selectCustomer({{ $customer->id }})">{{ __('Review') }}</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                    {{ $customers->links() }}
                </div>
            @else
                <x-ui.empty-state :title="__('No customers found')" :description="__('Customer accounts will appear here after registration or checkout.')" />
            @endif
        </div>

        <aside class="space-y-4">
            @if ($selectedCustomer)
                <form wire:submit="updateCustomer" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Profile') }}</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Customer since :date', ['date' => $selectedCustomer->created_at?->format('M j, Y')]) }}</p>
                        </div>
                        <x-ui.badge tone="teal">{{ trans_choice(':count order|:count orders', $selectedCustomer->orders->count(), ['count' => $selectedCustomer->orders->count()]) }}</x-ui.badge>
                    </div>

                    <div class="mt-4 grid gap-4">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Name') }}
                            <input wire:model="form.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="form.name" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Email') }}
                            <input wire:model="form.email" type="email" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="form.email" />
                        </label>
                    </div>

                    @can('update', $selectedCustomer)
                        <div class="mt-4">
                            <x-ui.button type="submit">{{ __('Save customer') }}</x-ui.button>
                        </div>
                    @endcan
                </form>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Recent orders') }}</h3>
                    <div class="mt-4 divide-y divide-zinc-200 dark:divide-white/10">
                        @forelse ($selectedCustomer->orders as $order)
                            <div class="flex items-center justify-between gap-3 py-3 text-sm" wire:key="customer-order-{{ $order->id }}">
                                <div>
                                    <div class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y H:i') ?? __('Not placed') }}</div>
                                </div>
                                <div class="text-right">
                                    <x-ui.badge>{{ str($order->status->value)->replace('_', ' ')->title() }}</x-ui.badge>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No orders yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Addresses') }}</h3>
                    <div class="mt-4 grid gap-3">
                        @forelse ($selectedCustomer->addresses as $address)
                            <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-white/5" wire:key="customer-address-{{ $address->id }}">
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $address->name }} / {{ $address->phone }}</div>
                                <div class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $address->line_one }}, {{ $address->city }}, {{ $address->country_code }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No saved addresses.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Reviews and wishlists') }}</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Reviews') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ number_format($selectedCustomer->productReviews->count()) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Wishlist items') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ number_format($selectedCustomer->wishlists->sum(fn ($wishlist) => $wishlist->items->count())) }}</dd>
                        </div>
                    </dl>
                </div>
            @else
                <x-ui.empty-state :title="__('Select a customer')" :description="__('Choose a customer to review profile details, orders, addresses, reviews, and wishlist activity.')" />
            @endif
        </aside>
    </div>
</section>
