<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Products')"
        :description="__('Manage product records, merchandising flags, publication state, taxonomy, and SEO foundations before variants and media are added in Phase 5.')"
    >
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <x-ui.button wire:click="create">{{ __('New product') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 lg:grid-cols-[1fr_auto_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search products') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name, slug, or SKU') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Status') }}
            <select wire:model.live="statusFilter" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="all">{{ __('All statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ str($status->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Brand') }}
            <select wire:model.live="brandFilter" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="">{{ __('All brands') }}</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="space-y-6 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit product') : __('Create product') }}</h2>
                <x-ui.badge tone="teal">{{ __('Phase 4 catalog') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 lg:col-span-2">
                    {{ __('Name') }}
                    <input wire:model="form.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.name" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Brand') }}
                    <select wire:model="form.brand_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('No brand') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.brand_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Slug') }}
                    <input wire:model="form.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.slug" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Base SKU') }}
                    <input wire:model="form.base_sku" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.base_sku" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model="form.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ str($status->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.status" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Published at') }}
                    <input wire:model="form.published_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.published_at" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Gender') }}
                    <input wire:model="form.gender" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('men, women, kids, unisex') }}">
                    <x-ui.input-error for="form.gender" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Material') }}
                    <input wire:model="form.material" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.material" />
                </label>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Short description') }}
                    <textarea wire:model="form.short_description" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.short_description" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Care instructions') }}
                    <textarea wire:model="form.care_instructions" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.care_instructions" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 lg:col-span-2">
                    {{ __('Description') }}
                    <textarea wire:model="form.description" rows="5" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.description" />
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Regular price') }}
                    <input wire:model="form.regular_price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.regular_price" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sale price') }}
                    <input wire:model="form.sale_price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sale_price" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Cost price') }}
                    <input wire:model="form.cost_price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.cost_price" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sale starts at') }}
                    <input wire:model="form.sale_starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sale_starts_at" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sale ends at') }}
                    <input wire:model="form.sale_ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sale_ends_at" />
                </label>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Categories') }}</legend>
                    <div class="mt-3 grid max-h-48 gap-2 overflow-y-auto pr-1">
                        @forelse ($categories as $category)
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                <input wire:model="form.category_ids" type="checkbox" value="{{ $category->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                {{ $category->name }}
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create categories before assigning them.') }}</p>
                        @endforelse
                    </div>
                    <x-ui.input-error for="form.category_ids" />
                </fieldset>

                <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Collections') }}</legend>
                    <div class="mt-3 grid max-h-48 gap-2 overflow-y-auto pr-1">
                        @forelse ($collections as $collection)
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                <input wire:model="form.collection_ids" type="checkbox" value="{{ $collection->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                {{ $collection->name }}
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create collections before assigning them.') }}</p>
                        @endforelse
                    </div>
                    <x-ui.input-error for="form.collection_ids" />
                </fieldset>

                <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Attributes') }}</legend>
                    <div class="mt-3 grid max-h-48 gap-3 overflow-y-auto pr-1">
                        @forelse ($attributeGroups as $attribute)
                            <div>
                                <p class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ $attribute->name }}</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach ($attribute->values as $value)
                                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                            <input wire:model="form.attribute_value_ids" type="checkbox" value="{{ $value->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                            {{ $value->display_value ?: $value->value }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create attributes before assigning them.') }}</p>
                        @endforelse
                    </div>
                    <x-ui.input-error for="form.attribute_value_ids" />
                </fieldset>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('SEO title') }}
                    <input wire:model="form.seo_title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.seo_title" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('SEO description') }}
                    <textarea wire:model="form.seo_description" rows="2" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.seo_description" />
                </label>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.is_featured" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Featured') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.is_new_arrival" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('New arrival') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.is_best_seller" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Best seller') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.track_inventory" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Track inventory') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.allow_backorder" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Allow backorder') }}
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save product') }}</x-ui.button>
            </div>
        </form>
    @endif

    @can('update', new App\Models\Product)
        <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto] dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ trans_choice(':count product selected|:count products selected', count($selectedProductIds), ['count' => count($selectedProductIds)]) }}
            </p>
            <select wire:model="bulkStatus" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" aria-label="{{ __('Bulk status') }}">
                @foreach ($bulkStatuses as $status)
                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <x-ui.button type="button" variant="secondary" wire:click="updateSelectedStatus">{{ __('Update selected') }}</x-ui.button>
            <x-ui.input-error for="selectedProductIds" />
        </div>
    @endcan

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($products->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">
                                <span class="sr-only">{{ __('Select') }}</span>
                            </th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Product') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Brand') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Price') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Catalog') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($products as $product)
                            <tr wire:key="product-{{ $product->id }}">
                                <td class="px-4 py-4 align-top">
                                    <input wire:model="selectedProductIds" type="checkbox" value="{{ $product->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600" aria-label="{{ __('Select :product', ['product' => $product->name]) }}">
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $product->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $product->base_sku ?: $product->slug }}</div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($product->is_featured)
                                            <x-ui.badge tone="amber">{{ __('Featured') }}</x-ui.badge>
                                        @endif
                                        @if ($product->is_new_arrival)
                                            <x-ui.badge tone="teal">{{ __('New') }}</x-ui.badge>
                                        @endif
                                        @if ($product->is_best_seller)
                                            <x-ui.badge>{{ __('Best seller') }}</x-ui.badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ $product->brand?->name ?? __('No brand') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <x-ui.badge :tone="$product->status === App\Enums\ProductStatus::Published ? 'teal' : ($product->status === App\Enums\ProductStatus::Scheduled ? 'amber' : 'neutral')">
                                        {{ str($product->status->value)->replace('_', ' ')->title() }}
                                    </x-ui.badge>
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $product->published_at?->format('M j, Y H:i') ?? __('Not published') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    @if ($product->sale_price)
                                        <span class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $product->sale_price, 2) }}</span>
                                        <span class="block text-xs line-through">BDT {{ number_format((float) $product->regular_price, 2) }}</span>
                                    @elseif ($product->regular_price)
                                        BDT {{ number_format((float) $product->regular_price, 2) }}
                                    @else
                                        {{ __('Not priced') }}
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <span class="block">{{ trans_choice(':count category|:count categories', $product->categories_count, ['count' => number_format($product->categories_count)]) }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count variant|:count variants', $product->variants_count, ['count' => number_format($product->variants_count)]) }} / {{ trans_choice(':count image|:count images', $product->images_count, ['count' => number_format($product->images_count)]) }}</span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $product)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $product->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $product)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('Delete this product?') }}">{{ __('Delete') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $products->links() }}
            </div>
        @else
            <x-ui.empty-state :title="__('No products found')" :description="__('Create a product or adjust your filters to see results.')" />
        @endif
    </div>
</section>
