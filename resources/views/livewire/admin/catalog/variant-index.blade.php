<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Product variants')"
        :description="__('Build product option combinations, maintain SKU-level pricing fields, and keep stock-ready variant records for storefront selection.')"
    >
        <x-slot:actions>
            @can('create', App\Models\ProductVariant::class)
                <x-ui.button wire:click="create">{{ __('New variant') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 lg:grid-cols-[1fr_auto_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search variants') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('SKU, barcode, option, or product') }}">
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

    @can('create', App\Models\ProductVariant::class)
        <form wire:submit="generateVariants" class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Generate from product attributes') }}
                <select wire:model="generationProductId" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <option value="">{{ __('Choose product') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}{{ $product->base_sku ? ' / '.$product->base_sku : '' }}</option>
                    @endforeach
                </select>
                <x-ui.input-error for="generationProductId" />
            </label>

            <div class="flex items-end">
                <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">{{ __('Generate variants') }}</x-ui.button>
            </div>
        </form>
    @endcan

    @if ($showForm)
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit variant') : __('Create variant') }}</h2>
                <x-ui.badge tone="teal">{{ __('SKU level') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Product') }}
                    <select wire:model="form.product_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('Choose product') }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.product_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('SKU') }}
                    <input wire:model="form.sku" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Auto from options when blank') }}">
                    <x-ui.input-error for="form.sku" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Barcode') }}
                    <input wire:model="form.barcode" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.barcode" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 lg:col-span-2">
                    {{ __('Option label') }}
                    <input wire:model="form.option_label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Auto from values when blank') }}">
                    <x-ui.input-error for="form.option_label" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="form.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sort_order" />
                </label>
            </div>

            <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Variant options') }}</legend>
                <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @forelse ($attributeGroups as $attribute)
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ $attribute->name }}</p>
                            @foreach ($attribute->values as $value)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    <input wire:model="form.attribute_value_ids" type="checkbox" value="{{ $value->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                    <span>{{ $value->display_value ?: $value->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mark attributes as variant options before assigning them to variants.') }}</p>
                    @endforelse
                </div>
                <x-ui.input-error for="form.attribute_value_ids" />
            </fieldset>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Price override') }}
                    <input wire:model="form.price_override" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.price_override" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Compare at price') }}
                    <input wire:model="form.compare_at_price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.compare_at_price" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Cost price') }}
                    <input wire:model="form.cost_price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.cost_price" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Stock quantity') }}
                    <input wire:model="form.stock_quantity" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.stock_quantity" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Reserved quantity') }}
                    <input wire:model="form.reserved_quantity" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.reserved_quantity" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Low-stock threshold') }}
                    <input wire:model="form.low_stock_threshold" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.low_stock_threshold" />
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Weight grams') }}
                    <input wire:model="form.weight_grams" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.weight_grams" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Length cm') }}
                    <input wire:model="form.length_cm" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.length_cm" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Width cm') }}
                    <input wire:model="form.width_cm" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.width_cm" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Height cm') }}
                    <input wire:model="form.height_cm" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.height_cm" />
                </label>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Active') }}
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.allow_backorder" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Allow backorder') }}
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save variant') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($variants->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Variant') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Product') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Options') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Stock') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Price') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($variants as $variant)
                            <tr wire:key="variant-{{ $variant->id }}">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $variant->sku }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $variant->barcode ?: __('No barcode') }}</div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-ui.badge :tone="$variant->is_active ? 'teal' : 'neutral'">{{ $variant->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                        @if ($variant->allow_backorder)
                                            <x-ui.badge tone="amber">{{ __('Backorder') }}</x-ui.badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $variant->product->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $variant->product->brand?->name ?? __('No brand') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <div>{{ $variant->option_label ?: __('Base variant') }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $variant->attributeValues->map(fn ($value) => ($value->productAttribute?->name ? $value->productAttribute->name.': ' : '').($value->display_value ?: $value->value))->implode(', ') ?: __('No options') }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    @php($availableQuantity = $variant->availableQuantity())
                                    <x-ui.badge :tone="$availableQuantity <= 0 ? 'rose' : (($variant->low_stock_threshold !== null && $availableQuantity <= $variant->low_stock_threshold) ? 'amber' : 'teal')">
                                        {{ number_format($availableQuantity) }} {{ __('available') }}
                                    </x-ui.badge>
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Stock') }} {{ number_format($variant->stock_quantity) }} / {{ __('Reserved') }} {{ number_format($variant->reserved_quantity) }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    @if ($variant->price_override)
                                        BDT {{ number_format((float) $variant->price_override, 2) }}
                                    @else
                                        {{ __('Product price') }}
                                    @endif
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count image|:count images', $variant->images_count, ['count' => number_format($variant->images_count)]) }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $variant)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $variant->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $variant)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $variant->id }})" wire:confirm="{{ __('Delete this variant?') }}">{{ __('Delete') }}</x-ui.button>
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
                :title="__('No variants found')"
                :description="__('Create a variant, generate variants from product attributes, or adjust your filters.')"
            />
        @endif
    </div>
</section>
