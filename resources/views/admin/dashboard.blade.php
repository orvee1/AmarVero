<x-layouts.admin
    :title="__('Admin overview')"
    :breadcrumbs="[__('Admin') => route('admin.dashboard'), __('Overview') => null]"
>
    <div class="space-y-8">
        <x-ui.section-heading
            :overline="__('Operations')"
            :title="__('Admin overview')"
            :description="__('A current snapshot from catalog, customer, inventory, order, coupon, and fulfillment tables.')"
        />

        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900 lg:grid-cols-[1fr_auto_auto_auto] lg:items-end">
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Date range') }}
                <select name="range" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    @foreach ($filters['range_options'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Start') }}
                <input name="start_date" type="date" value="{{ $filters['start_date'] }}" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('End') }}
                <input name="end_date" type="date" value="{{ $filters['end_date'] }}" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            </label>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit" variant="secondary">{{ __('Apply') }}</x-ui.button>
                <x-ui.button :href="route('admin.dashboard')" variant="subtle">{{ __('Reset') }}</x-ui.button>
            </div>

            <div class="lg:col-span-4">
                <x-ui.badge tone="teal">{{ $filters['label'] }}</x-ui.badge>
                <x-ui.badge>{{ __('Previous: :period', ['period' => $filters['previous_label']]) }}</x-ui.badge>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <x-ui.stat-card
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :description="$metric['description']"
                    :tone="$metric['tone']"
                    :trend="$metric['trend']"
                />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Catalog foundation') }}</h2>
                <dl class="mt-5 grid gap-4">
                    @foreach ($catalogSummary as $label => $value)
                        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0 dark:border-white/10">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ $label }}</dt>
                            <dd class="text-sm font-semibold text-zinc-950 dark:text-white">{{ number_format($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Order pipeline') }}</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($orderStatusCounts as $status)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                            <x-ui.badge :tone="$status['tone']">{{ $status['label'] }}</x-ui.badge>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($status['count']) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Best-selling products') }}</h2>

                @if ($bestSellingProducts !== [])
                    <div class="mt-5 space-y-4">
                        @foreach ($bestSellingProducts as $product)
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0 dark:border-white/10">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $product['name'] }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $product['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ number_format($product['units']) }} {{ __('sold') }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $product['revenue'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state class="mt-5" :title="__('No product sales')" :description="__('No order items were captured in this period.')" />
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Top categories and brands') }}</h2>

                <div class="mt-5 grid gap-6">
                    <div>
                        <h3 class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Categories') }}</h3>
                        <div class="mt-3 space-y-3">
                            @forelse ($topCategories as $category)
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-zinc-700 dark:text-zinc-200">{{ $category['name'] }}</span>
                                    <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($category['units']) }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No category sales yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Brands') }}</h3>
                        <div class="mt-3 space-y-3">
                            @forelse ($topBrands as $brand)
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-zinc-700 dark:text-zinc-200">{{ $brand['name'] }}</span>
                                    <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($brand['units']) }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No brand sales yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Coupon usage') }}</h2>

                @if ($couponUsage !== [])
                    <div class="mt-5 space-y-4">
                        @foreach ($couponUsage as $coupon)
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0 dark:border-white/10">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $coupon['code'] }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $coupon['name'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ number_format($coupon['redemptions']) }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $coupon['discount'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state class="mt-5" :title="__('No coupon usage')" :description="__('No coupon redemptions were recorded in this period.')" />
                @endif
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Inventory watchlist') }}</h2>

                @if ($stockWatchlist !== [])
                    <div class="mt-5 space-y-4">
                        @foreach ($stockWatchlist as $variant)
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 last:border-b-0 last:pb-0 dark:border-white/10">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $variant['product'] }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $variant['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <x-ui.badge :tone="$variant['tone']">{{ $variant['available'] <= 0 ? __('Out of stock') : __('Low stock') }}</x-ui.badge>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Threshold: :count', ['count' => number_format($variant['threshold'])]) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state class="mt-5" :title="__('Stock is healthy')" :description="__('No active variants are currently at or below their low-stock threshold.')" />
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Recent orders') }}</h2>
                    <x-ui.badge>{{ __('Live data') }}</x-ui.badge>
                </div>

                @if ($recentOrders->isNotEmpty())
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-white/10">
                            <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="py-3 pr-4 font-semibold">{{ __('Order') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Customer') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                    <th scope="col" class="py-3 pl-4 text-right font-semibold">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="py-4 pr-4 font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</td>
                                        <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $order->customer_name }}</td>
                                        <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ str($order->status->value)->replace('_', ' ')->title() }}</td>
                                        <td class="py-4 pl-4 text-right font-medium text-zinc-950 dark:text-white">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state
                        class="mt-5"
                        :title="__('No recent orders')"
                        :description="__('There are no order records in the database yet.')"
                    />
                @endif
            </section>
        </div>
    </div>
</x-layouts.admin>
