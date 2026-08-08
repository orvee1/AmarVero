<x-layouts.admin
    :title="__('Admin overview')"
    :breadcrumbs="[__('Admin') => route('admin.dashboard'), __('Overview') => null]"
>
    <div class="space-y-8">
        <x-ui.section-heading
            :overline="__('Operations')"
            :title="__('Admin overview')"
            :description="__('A real-time snapshot from the current catalog, customer, inventory, and order tables.')"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($metrics as $metric)
                <x-ui.stat-card
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :description="$metric['description']"
                    :tone="$metric['tone']"
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
