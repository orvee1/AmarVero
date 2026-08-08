<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Product images')"
        :description="__('Upload product media, assign variant-specific imagery, select primary images, and control gallery ordering.')"
    >
        <x-slot:actions>
            @can('create', App\Models\ProductImage::class)
                <x-ui.button wire:click="create">{{ __('New image') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search images') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Product, SKU, alt text, or path') }}">
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
    </div>

    @if ($showForm)
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit image') : __('Add image') }}</h2>
                <x-ui.badge tone="teal">{{ __('Public disk') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Product') }}
                    <select wire:model.live="form.product_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('Choose product') }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.product_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Variant') }}
                    <select wire:model="form.product_variant_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('Whole product') }}</option>
                        @foreach ($variantsForForm as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->sku }}{{ $variant->option_label ? ' / '.$variant->option_label : '' }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.product_variant_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="form.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sort_order" />
                </label>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Upload image') }}
                    <input wire:model="imageUpload" type="file" accept="image/*" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-950 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white dark:border-white/15 dark:bg-zinc-950 dark:text-white dark:file:bg-white dark:file:text-zinc-950">
                    <x-ui.input-error for="imageUpload" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Existing storage path') }}
                    <input wire:model="form.path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('products/1/image.jpg') }}">
                    <x-ui.input-error for="form.path" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 lg:col-span-2">
                    {{ __('Alt text') }}
                    <input wire:model="form.alt_text" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.alt_text" />
                </label>
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input wire:model="form.is_primary" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                {{ __('Primary product image') }}
            </label>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save image') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($images->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Preview') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Product') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Placement') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Path') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($images as $image)
                            <tr wire:key="image-{{ $image->id }}">
                                <td class="px-4 py-4 align-top">
                                    <img
                                        src="{{ Illuminate\Support\Facades\Storage::disk($image->disk)->url($image->path) }}"
                                        alt="{{ $image->alt_text ?: $image->product->name }}"
                                        class="aspect-square w-16 rounded-lg border border-zinc-200 object-cover dark:border-white/10"
                                        loading="lazy"
                                    >
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $image->product->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $image->product->brand?->name ?? __('No brand') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($image->is_primary)
                                            <x-ui.badge tone="teal">{{ __('Primary') }}</x-ui.badge>
                                        @endif
                                        <x-ui.badge>{{ __('Order :order', ['order' => $image->sort_order]) }}</x-ui.badge>
                                    </div>
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $image->productVariant?->sku ?? __('Whole product') }}</div>
                                </td>
                                <td class="max-w-xs px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <div class="truncate">{{ $image->path }}</div>
                                    <div class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $image->alt_text ?: __('No alt text') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $image)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $image->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $image)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $image->id }})" wire:confirm="{{ __('Delete this image?') }}">{{ __('Delete') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $images->links() }}
            </div>
        @else
            <x-ui.empty-state
                :title="__('No product images found')"
                :description="__('Upload images or assign existing storage paths to products.')"
            />
        @endif
    </div>
</section>
