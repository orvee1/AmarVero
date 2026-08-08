<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Size guides')"
        :description="__('Maintain fit guidance by brand, category, and product so future product-detail pages can show the right measurement help.')"
    >
        <x-slot:actions>
            @can('create', App\Models\SizeGuide::class)
                <x-ui.button wire:click="create">{{ __('New size guide') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search size guides') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name, slug, or content') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Status') }}
            <select wire:model.live="status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="all">{{ __('All guides') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </label>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit size guide') : __('Create size guide') }}</h2>
                <x-ui.badge tone="teal">{{ __('Fit content') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Name') }}
                    <input wire:model="form.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.name" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Slug') }}
                    <input wire:model="form.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.slug" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Brand scope') }}
                    <select wire:model="form.brand_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('Any brand') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.brand_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Category scope') }}
                    <select wire:model="form.category_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('Any category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.category_id" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">
                    {{ __('Content') }}
                    <textarea wire:model="form.content" rows="4" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.content" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">
                    {{ __('Measurements') }}
                    <textarea wire:model="form.measurements_text" rows="5" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('EU 40: Foot length 25.5 cm') }}"></textarea>
                    <x-ui.input-error for="form.measurements_text" />
                </label>
            </div>

            <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Assigned products') }}</legend>
                <div class="mt-3 grid max-h-56 gap-2 overflow-y-auto pr-1 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($products as $product)
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input wire:model="form.product_ids" type="checkbox" value="{{ $product->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            <span>{{ $product->name }}{{ $product->base_sku ? ' / '.$product->base_sku : '' }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create products before assigning size guides.') }}</p>
                    @endforelse
                </div>
                <x-ui.input-error for="form.product_ids" />
            </fieldset>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input wire:model="form.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                {{ __('Active') }}
            </label>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save size guide') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($sizeGuides->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Guide') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Scope') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Products') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($sizeGuides as $sizeGuide)
                            <tr wire:key="size-guide-{{ $sizeGuide->id }}">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $sizeGuide->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $sizeGuide->slug }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <span class="block">{{ $sizeGuide->brand?->name ?? __('Any brand') }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $sizeGuide->category?->name ?? __('Any category') }}</span>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format($sizeGuide->products_count) }}</td>
                                <td class="px-4 py-4 align-top">
                                    <x-ui.badge :tone="$sizeGuide->is_active ? 'teal' : 'neutral'">{{ $sizeGuide->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $sizeGuide)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $sizeGuide->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $sizeGuide)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $sizeGuide->id }})" wire:confirm="{{ __('Delete this size guide?') }}">{{ __('Delete') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $sizeGuides->links() }}
            </div>
        @else
            <x-ui.empty-state
                :title="__('No size guides found')"
                :description="__('Create fit guidance or adjust your filters to see existing guides.')"
            />
        @endif
    </div>
</section>
