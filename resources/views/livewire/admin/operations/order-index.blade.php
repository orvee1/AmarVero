<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Operations')"
        :title="__('Orders')"
        :description="__('Review orders, update fulfillment and payment status, and keep an internal timeline for support handoffs.')"
    />

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 lg:grid-cols-3 dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search orders') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Order, customer, email, phone') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Order status') }}
            <select wire:model.live="status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($orderStatuses as $orderStatus)
                    <option value="{{ $orderStatus->value }}">{{ str($orderStatus->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Payment status') }}
            <select wire:model.live="paymentStatus" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="">{{ __('All payments') }}</option>
                @foreach ($paymentStatuses as $paymentStatusOption)
                    <option value="{{ $paymentStatusOption->value }}">{{ str($paymentStatusOption->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(380px,0.95fr)]">
        <x-admin.table-region
            :label="__('Orders table')"
            :scroll-hint="__('Scroll sideways to review customer, status, totals, and actions.')"
        >
            @if ($orders->isNotEmpty())
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Order') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Customer') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Total') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->id }}" class="@if ($selectedOrder?->id === $order->id) bg-teal-50/70 dark:bg-teal-400/10 @endif">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y H:i') ?? __('Not placed') }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $order->shippingMethod?->name ?? __('No shipping method') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <span class="block font-medium text-zinc-950 dark:text-white">{{ $order->customer_name }}</span>
                                    <span class="block text-xs">{{ $order->email }}</span>
                                    <span class="block text-xs">{{ trans_choice(':count item|:count items', $order->items->count(), ['count' => $order->items->count()]) }}</span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge :tone="in_array($order->status, [App\Enums\OrderStatus::Delivered, App\Enums\OrderStatus::Shipped], true) ? 'teal' : ($order->status === App\Enums\OrderStatus::Cancelled ? 'rose' : 'amber')">
                                            {{ str($order->status->value)->replace('_', ' ')->title() }}
                                        </x-ui.badge>
                                        <x-ui.badge :tone="$order->payment_status === App\Enums\PaymentStatus::Paid ? 'teal' : ($order->payment_status === App\Enums\PaymentStatus::Failed ? 'rose' : 'neutral')">
                                            {{ str($order->payment_status->value)->replace('_', ' ')->title() }}
                                        </x-ui.badge>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top font-semibold text-zinc-950 dark:text-white">
                                    {{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end">
                                        <x-ui.button size="sm" variant="secondary" wire:click="selectOrder({{ $order->id }})">{{ __('Review') }}</x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-slot:footer>
                    {{ $orders->links() }}
                </x-slot:footer>
            @else
                <x-ui.empty-state :title="__('No orders found')" :description="__('Orders will appear here once customers complete checkout.')" />
            @endif
        </x-admin.table-region>

        <aside class="space-y-4">
            @if ($selectedOrder)
                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $selectedOrder->order_number }}</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $selectedOrder->customer_name }} / {{ $selectedOrder->email }}</p>
                        </div>
                        <x-ui.badge tone="teal">{{ $selectedOrder->currency_code }} {{ number_format((float) $selectedOrder->grand_total, 2) }}</x-ui.badge>
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ number_format((float) $selectedOrder->subtotal, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Shipping') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ number_format((float) $selectedOrder->shipping_total, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Discount') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ number_format((float) $selectedOrder->discount_total, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ $selectedOrder->phone ?: __('Not provided') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Order controls') }}</h3>

                    <div class="mt-4 grid gap-4">
                        @can('updateStatus', $selectedOrder)
                            <form wire:submit="updateStatus" class="grid gap-3">
                                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ __('Fulfillment status') }}
                                    <select wire:model="form.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                        @foreach ($orderStatuses as $orderStatus)
                                            <option value="{{ $orderStatus->value }}">{{ str($orderStatus->value)->replace('_', ' ')->title() }}</option>
                                        @endforeach
                                    </select>
                                    <x-ui.input-error for="form.status" />
                                </label>

                                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ __('Status note') }}
                                    <textarea wire:model="form.note" rows="2" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                                    <x-ui.input-error for="form.note" />
                                </label>

                                <x-ui.button type="submit">{{ __('Update order') }}</x-ui.button>
                            </form>
                        @endcan

                        @can('updatePayment', $selectedOrder)
                            <form wire:submit="updatePayment" class="grid gap-3 border-t border-zinc-200 pt-4 dark:border-white/10">
                                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ __('Payment status') }}
                                    <select wire:model="form.payment_status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                        @foreach ($paymentStatuses as $paymentStatusOption)
                                            <option value="{{ $paymentStatusOption->value }}">{{ str($paymentStatusOption->value)->replace('_', ' ')->title() }}</option>
                                        @endforeach
                                    </select>
                                    <x-ui.input-error for="form.payment_status" />
                                </label>

                                <x-ui.button type="submit" variant="secondary">{{ __('Update payment') }}</x-ui.button>
                            </form>
                        @endcan
                    </div>
                </div>

                @can('create', App\Models\OrderNote::class)
                    <form wire:submit="addNote" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Internal note') }}</h3>
                        <label class="mt-4 grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Note') }}
                            <textarea wire:model="noteBody" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                            <x-ui.input-error for="noteBody" />
                        </label>
                        <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="noteVisibleToCustomer" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Visible to customer') }}
                        </label>
                        <div class="mt-4">
                            <x-ui.button type="submit" variant="secondary">{{ __('Save note') }}</x-ui.button>
                        </div>
                    </form>
                @endcan

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Items') }}</h3>
                    <div class="mt-4 divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($selectedOrder->items as $item)
                            <div class="py-3 text-sm" wire:key="selected-order-item-{{ $item->id }}">
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $item->product_name }}</div>
                                <div class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $item->variant_name ?: $item->sku }} / {{ __('Qty :count', ['count' => $item->quantity]) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Timeline') }}</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($selectedOrder->statusEvents->sortByDesc('created_at') as $event)
                            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-white/5" wire:key="order-event-{{ $event->id }}">
                                <div class="font-medium text-zinc-950 dark:text-white">{{ str($event->from_status?->value ?? 'new')->replace('_', ' ')->title() }} -> {{ str($event->to_status->value)->replace('_', ' ')->title() }}</div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $event->created_at?->format('M j, Y H:i') }} / {{ $event->user?->name ?? __('System') }}</div>
                                @if ($event->note)
                                    <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $event->note }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No status events recorded yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Notes') }}</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($selectedOrder->notes->sortByDesc('created_at') as $note)
                            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-white/5" wire:key="order-note-{{ $note->id }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-medium text-zinc-950 dark:text-white">{{ $note->user?->name ?? __('System') }}</span>
                                    <x-ui.badge :tone="$note->is_customer_visible ? 'teal' : 'neutral'">{{ $note->is_customer_visible ? __('Customer visible') : __('Internal') }}</x-ui.badge>
                                </div>
                                <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $note->body }}</p>
                            </div>
                        @empty
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No notes yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            @else
                <x-ui.empty-state :title="__('Select an order')" :description="__('Choose an order from the list to review the customer, items, status controls, and notes.')" />
            @endif
        </aside>
    </div>
</section>
