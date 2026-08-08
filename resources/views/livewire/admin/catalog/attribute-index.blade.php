<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Attributes')"
        :description="__('Manage filterable product attributes and their values for catalog discovery and variant preparation.')"
    >
        <x-slot:actions>
            @can('create', App\Models\ProductAttribute::class)
                <x-ui.button wire:click="createAttribute">{{ __('New attribute') }}</x-ui.button>
            @endcan
            @can('create', App\Models\AttributeValue::class)
                <x-ui.button variant="secondary" wire:click="createValue">{{ __('New value') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="space-y-4">
            <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Search attributes') }}
                    <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or slug') }}">
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model.live="status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="all">{{ __('All') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </label>
            </div>

            @if ($showAttributeForm)
                <form wire:submit="saveAttribute" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingAttributeId ? __('Edit attribute') : __('Create attribute') }}</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Name') }}
                            <input wire:model="attributeForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="attributeForm.name" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Slug') }}
                            <input wire:model="attributeForm.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="attributeForm.slug" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Type') }}
                            <select wire:model="attributeForm.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                @foreach ($types as $type)
                                    <option value="{{ $type }}">{{ str($type)->title() }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="attributeForm.type" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Sort order') }}
                            <input wire:model="attributeForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="attributeForm.sort_order" />
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="attributeForm.is_variant_option" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Variant option') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="attributeForm.is_filterable" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Filterable') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="attributeForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Active') }}
                        </label>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="cancelAttribute">{{ __('Cancel') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save attribute') }}</x-ui.button>
                    </div>
                </form>
            @endif

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                @if ($attributes->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Attribute') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Flags') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($attributes as $attribute)
                                    <tr wire:key="attribute-{{ $attribute->id }}">
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-zinc-950 dark:text-white">{{ $attribute->name }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $attribute->slug }} / {{ str($attribute->type)->title() }}</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <x-ui.badge :tone="$attribute->is_active ? 'teal' : 'neutral'">{{ $attribute->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                                @if ($attribute->is_variant_option)
                                                    <x-ui.badge tone="amber">{{ __('Variant') }}</x-ui.badge>
                                                @endif
                                                @if ($attribute->is_filterable)
                                                    <x-ui.badge>{{ __('Filter') }}</x-ui.badge>
                                                @endif
                                                <x-ui.badge>{{ trans_choice(':count value|:count values', $attribute->values_count, ['count' => number_format($attribute->values_count)]) }}</x-ui.badge>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <x-ui.button size="sm" variant="secondary" wire:click="$set('selectedAttributeId', '{{ $attribute->id }}')">{{ __('Values') }}</x-ui.button>
                                                @can('update', $attribute)
                                                    <x-ui.button size="sm" variant="secondary" wire:click="editAttribute({{ $attribute->id }})">{{ __('Edit') }}</x-ui.button>
                                                @endcan
                                                @can('delete', $attribute)
                                                    <x-ui.button size="sm" variant="danger" wire:click="deleteAttribute({{ $attribute->id }})" wire:confirm="{{ __('Delete this attribute and its values?') }}">{{ __('Delete') }}</x-ui.button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $attributes->links() }}</div>
                @else
                    <x-ui.empty-state :title="__('No attributes found')" :description="__('Create an attribute to define product sizes, colors, materials, or other filters.')" />
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-2 dark:border-white/10 dark:bg-zinc-900">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Attribute') }}
                    <select wire:model.live="selectedAttributeId" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <option value="">{{ __('All attributes') }}</option>
                        @foreach ($attributeOptions as $attribute)
                            <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Search values') }}
                    <input type="search" wire:model.live.debounce.300ms="valueSearch" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Value, display, or slug') }}">
                </label>
            </div>

            @if ($showValueForm)
                <form wire:submit="saveValue" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingValueId ? __('Edit value') : __('Create value') }}</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Attribute') }}
                            <select wire:model="valueForm.product_attribute_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('Choose attribute') }}</option>
                                @foreach ($attributeOptions as $attribute)
                                    <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="valueForm.product_attribute_id" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Value') }}
                            <input wire:model="valueForm.value" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="valueForm.value" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Slug') }}
                            <input wire:model="valueForm.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="valueForm.slug" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Display value') }}
                            <input wire:model="valueForm.display_value" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="valueForm.display_value" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Color hex') }}
                            <input wire:model="valueForm.color_hex" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="#111111">
                            <x-ui.input-error for="valueForm.color_hex" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Sort order') }}
                            <input wire:model="valueForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="valueForm.sort_order" />
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <input wire:model="valueForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                        {{ __('Active') }}
                    </label>

                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="cancelValue">{{ __('Cancel') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save value') }}</x-ui.button>
                    </div>
                </form>
            @endif

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                @if ($values->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Value') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Attribute') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($values as $value)
                                    <tr wire:key="value-{{ $value->id }}">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 font-semibold text-zinc-950 dark:text-white">
                                                @if ($value->color_hex)
                                                    <span class="size-4 rounded-full border border-zinc-300" style="background-color: {{ $value->color_hex }}"></span>
                                                @endif
                                                {{ $value->display_value ?: $value->value }}
                                            </div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $value->slug }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $value->productAttribute?->name }}</td>
                                        <td class="px-4 py-4"><x-ui.badge :tone="$value->is_active ? 'teal' : 'neutral'">{{ $value->is_active ? __('Active') : __('Inactive') }}</x-ui.badge></td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                @can('update', $value)
                                                    <x-ui.button size="sm" variant="secondary" wire:click="editValue({{ $value->id }})">{{ __('Edit') }}</x-ui.button>
                                                @endcan
                                                @can('delete', $value)
                                                    <x-ui.button size="sm" variant="danger" wire:click="deleteValue({{ $value->id }})" wire:confirm="{{ __('Delete this value?') }}">{{ __('Delete') }}</x-ui.button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $values->links() }}</div>
                @else
                    <x-ui.empty-state :title="__('No values found')" :description="__('Create values after defining at least one product attribute.')" />
                @endif
            </div>
        </div>
    </div>
</section>
