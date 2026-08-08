<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Inventory')"
        :description="__('Review SKU-level availability, apply audited stock movements, and spot low or out-of-stock variants before they hit the storefront.')"
    />

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 lg:grid-cols-[1fr_auto_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search inventory') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('SKU, option, or product') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Product') }}
            <select wire:model.live="productFilter" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="">{{ __('All products') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Stock') }}
            <select wire:model.live="stockFilter" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="all">{{ __('All stock') }}</option>
                <option value="available">{{ __('Available') }}</option>
                <option value="low">{{ __('Low stock') }}</option>
                <option value="out">{{ __('Out of stock') }}</option>
            </select>
        </label>
    </div>

    @can('create', App\Models\InventoryMovement::class)
        <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_auto_auto_auto] dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ trans_choice(':count variant selected|:count variants selected', count($selectedVariantIds), ['count' => count($selectedVariantIds)]) }}
            </p>

            <select wire:model="bulkMovementType" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" aria-label="{{ __('Bulk movement type') }}">
                @foreach ($movementTypes as $type)
                    <option value="{{ $type->value }}">{{ str($type->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>

            <input wire:model="bulkAdjustmentQuantity" type="number" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Quantity +/-') }}" aria-label="{{ __('Bulk quantity') }}">

            <x-ui.button type="button" variant="secondary" wire:click="bulkAdjustSelected">{{ __('Apply to selected') }}</x-ui.button>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-4 dark:text-zinc-200">
                {{ __('Bulk reason') }}
                <input wire:model="bulkAdjustmentReason" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Cycle count, stock receipt, correction') }}">
            </label>

            <div class="md:col-span-4">
                <x-ui.input-error for="selectedVariantIds" />
                <x-ui.input-error for="bulkAdjustmentQuantity" />
                <x-ui.input-error for="bulkMovementType" />
                <x-ui.input-error for="bulkAdjustmentReason" />
            </div>
        </div>
    @endcan

    @if ($showAdjustmentForm)
        <form wire:submit="saveAdjustment" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Adjust inventory') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $adjustmentForm['product_name'] }} / {{ $adjustmentForm['sku'] }}</p>
                </div>
                <x-ui.badge tone="amber">{{ __('Movement audit') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Movement type') }}
                    <select wire:model="adjustmentForm.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($movementTypes as $type)
                            <option value="{{ $type->value }}">{{ str($type->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="adjustmentForm.type" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Quantity change') }}
                    <input wire:model="adjustmentForm.quantity" type="number" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Use negative to reduce') }}">
                    <x-ui.input-error for="adjustmentForm.quantity" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Reason') }}
                    <input wire:model="adjustmentForm.reason" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="adjustmentForm.reason" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-3 dark:text-zinc-200">
                    {{ __('Notes') }}
                    <textarea wire:model="adjustmentForm.notes" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="adjustmentForm.notes" />
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancelAdjustment">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Record movement') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($variants->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">
                                <span class="sr-only">{{ __('Select') }}</span>
                            </th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Variant') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Available') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Stock detail') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Movements') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($variants as $variant)
                            @php($availableQuantity = $variant->availableQuantity())
                            <tr wire:key="inventory-variant-{{ $variant->id }}">
                                <td class="px-4 py-4 align-top">
                                    <input wire:model="selectedVariantIds" type="checkbox" value="{{ $variant->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600" aria-label="{{ __('Select :sku', ['sku' => $variant->sku]) }}">
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $variant->sku }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $variant->product->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $variant->option_label ?: __('Base variant') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <x-ui.badge :tone="$availableQuantity <= 0 ? 'rose' : (($variant->low_stock_threshold !== null && $availableQuantity <= $variant->low_stock_threshold) ? 'amber' : 'teal')">
                                        {{ number_format($availableQuantity) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <span class="block">{{ __('On hand') }}: {{ number_format($variant->stock_quantity) }}</span>
                                    <span class="block">{{ __('Reserved') }}: {{ number_format($variant->reserved_quantity) }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('Low threshold') }}: {{ $variant->low_stock_threshold === null ? __('None') : number_format($variant->low_stock_threshold) }}</span>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format($variant->inventory_movements_count) }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('create', App\Models\InventoryMovement::class)
                                            <x-ui.button size="sm" variant="secondary" wire:click="adjust({{ $variant->id }})">{{ __('Adjust') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $variants->links() }}
            </div>
        @else
            <x-ui.empty-state
                :title="__('No inventory found')"
                :description="__('Create product variants before adjusting inventory.')"
            />
        @endif
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Recent inventory movements') }}</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                <thead class="text-left text-xs uppercase text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('Time') }}</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('Variant') }}</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('Type') }}</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('Qty') }}</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('Balance') }}</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">{{ __('User') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                    @forelse ($recentMovements as $movement)
                        <tr>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ $movement->created_at->format('M j, H:i') }}</td>
                            <td class="py-3 pr-4">
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $movement->productVariant->sku }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $movement->productVariant->product->name }}</div>
                            </td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ str($movement->type->value)->replace('_', ' ')->title() }}</td>
                            <td class="py-3 pr-4 font-semibold {{ $movement->quantity < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-teal-700 dark:text-teal-300' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}</td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ number_format($movement->balance_after) }}</td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ $movement->user?->name ?? __('System') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No inventory movements yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
