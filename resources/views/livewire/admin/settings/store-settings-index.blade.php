<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Settings')"
        :title="__('Store settings')"
        :description="__('Manage public brand details, SEO defaults, operational rules, newsletter behavior, invoice references, and shipping rates without exposing provider secrets.')"
    />

    <div class="flex flex-wrap gap-2 rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-900">
        <button type="button" wire:click="showPanel('settings')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'settings' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Store settings') }}</button>
        <button type="button" wire:click="showPanel('shipping')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'shipping' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Shipping') }}</button>
    </div>

    @if ($panel === 'settings')
        <form wire:submit="saveSettings" class="space-y-6">
            @foreach ($settingsGroups as $group => $settings)
                <fieldset class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <legend class="text-base font-semibold text-zinc-950 dark:text-white">{{ str($group)->replace('_', ' ')->title() }}</legend>
                        <x-ui.badge>{{ trans_choice(':count setting|:count settings', count($settings), ['count' => count($settings)]) }}</x-ui.badge>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        @foreach ($settings as $setting)
                            @php($model = 'settingsForm.'.$setting['form_key'])

                            @if ($setting['type'] === 'boolean')
                                <label class="flex min-h-10 items-center gap-3 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 dark:border-white/10 dark:text-zinc-200">
                                    <input wire:model="{{ $model }}" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                    <span>{{ $setting['label'] }}</span>
                                    @if ($setting['public'])
                                        <x-ui.badge tone="teal">{{ __('Public') }}</x-ui.badge>
                                    @endif
                                </label>
                                <x-ui.input-error for="{{ $model }}" />
                            @elseif (str_contains($setting['key'], 'description') || str_contains($setting['key'], 'instructions'))
                                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 lg:col-span-2">
                                    <span class="flex items-center gap-2">
                                        {{ $setting['label'] }}
                                        @if ($setting['public'])
                                            <x-ui.badge tone="teal">{{ __('Public') }}</x-ui.badge>
                                        @endif
                                    </span>
                                    <textarea wire:model="{{ $model }}" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                                    <x-ui.input-error for="{{ $model }}" />
                                </label>
                            @else
                                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                    <span class="flex items-center gap-2">
                                        {{ $setting['label'] }}
                                        @if ($setting['public'])
                                            <x-ui.badge tone="teal">{{ __('Public') }}</x-ui.badge>
                                        @endif
                                    </span>
                                    <input wire:model="{{ $model }}" type="{{ $setting['type'] === 'integer' ? 'number' : 'text' }}" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                    <x-ui.input-error for="{{ $model }}" />
                                </label>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            @can('update', new App\Models\SiteSetting)
                <div class="flex justify-end">
                    <x-ui.button type="submit">{{ __('Save settings') }}</x-ui.button>
                </div>
            @endcan
        </form>
    @else
        <div class="grid gap-6 xl:grid-cols-[minmax(340px,0.7fr)_minmax(0,1fr)]">
            @can('update', new App\Models\ShippingZone)
                <div class="space-y-6">
                    <form wire:submit="saveZone" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingZoneId ? __('Edit zone') : __('Create zone') }}</h3>
                            <x-ui.badge>{{ __('Zone') }}</x-ui.badge>
                        </div>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Name') }}
                            <input wire:model="zoneForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="zoneForm.name" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Countries') }}
                            <input wire:model="zoneForm.countries" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('BD, US') }}">
                            <x-ui.input-error for="zoneForm.countries" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Regions') }}
                            <input wire:model="zoneForm.regions" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Dhaka, Chattogram') }}">
                            <x-ui.input-error for="zoneForm.regions" />
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Sort order') }}
                                <input wire:model="zoneForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="zoneForm.sort_order" />
                            </label>

                            <label class="mt-7 inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                <input wire:model="zoneForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                {{ __('Active') }}
                            </label>
                        </div>

                        <div class="flex justify-end gap-2">
                            <x-ui.button type="button" variant="secondary" wire:click="createZone">{{ __('Reset') }}</x-ui.button>
                            <x-ui.button type="submit">{{ __('Save zone') }}</x-ui.button>
                        </div>
                    </form>

                    <form wire:submit="saveMethod" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingMethodId ? __('Edit method') : __('Create method') }}</h3>
                            <x-ui.badge>{{ __('Rate') }}</x-ui.badge>
                        </div>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Zone') }}
                            <select wire:model="methodForm.shipping_zone_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('Choose a zone') }}</option>
                                @foreach ($shippingZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="methodForm.shipping_zone_id" />
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Name') }}
                                <input wire:model="methodForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.name" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Code') }}
                                <input wire:model="methodForm.code" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.code" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Price') }}
                                <input wire:model="methodForm.price" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.price" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Free shipping threshold') }}
                                <input wire:model="methodForm.free_shipping_threshold" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.free_shipping_threshold" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Min days') }}
                                <input wire:model="methodForm.estimated_days_min" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.estimated_days_min" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Max days') }}
                                <input wire:model="methodForm.estimated_days_max" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.estimated_days_max" />
                            </label>

                            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Sort order') }}
                                <input wire:model="methodForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <x-ui.input-error for="methodForm.sort_order" />
                            </label>

                            <label class="mt-7 inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                <input wire:model="methodForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                                {{ __('Active') }}
                            </label>
                        </div>

                        <div class="flex justify-end gap-2">
                            <x-ui.button type="button" variant="secondary" wire:click="createMethod">{{ __('Reset') }}</x-ui.button>
                            <x-ui.button type="submit">{{ __('Save method') }}</x-ui.button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="space-y-4">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    @if ($shippingZones->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                                <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Zone') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Coverage') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Methods') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                    @foreach ($shippingZones as $zone)
                                        <tr wire:key="shipping-zone-{{ $zone->id }}">
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-semibold text-zinc-950 dark:text-white">{{ $zone->name }}</div>
                                                <div class="mt-2">
                                                    <x-ui.badge :tone="$zone->is_active ? 'teal' : 'neutral'">{{ $zone->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                                <span class="block">{{ $zone->countries ? implode(', ', $zone->countries) : __('Any country') }}</span>
                                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $zone->regions ? implode(', ', $zone->regions) : __('All regions') }}</span>
                                            </td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format($zone->methods->count()) }}</td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex justify-end gap-2">
                                                    @can('update', $zone)
                                                        <x-ui.button size="sm" variant="secondary" wire:click="editZone({{ $zone->id }})">{{ __('Edit') }}</x-ui.button>
                                                        <x-ui.button size="sm" variant="danger" wire:click="deleteZone({{ $zone->id }})" wire:confirm="{{ __('Delete this shipping zone?') }}">{{ __('Delete') }}</x-ui.button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('No shipping zones')" :description="__('Create a zone before adding shipping methods.')" />
                    @endif
                </div>

                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    @if ($shippingMethods->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                                <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Method') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Zone') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Rate') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                    @foreach ($shippingMethods as $method)
                                        <tr wire:key="shipping-method-{{ $method->id }}">
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-semibold text-zinc-950 dark:text-white">{{ $method->name }}</div>
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $method->code }}</div>
                                                <div class="mt-2">
                                                    <x-ui.badge :tone="$method->is_active ? 'teal' : 'neutral'">{{ $method->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ $method->shippingZone?->name ?? __('No zone') }}</td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                                <span class="block">BDT {{ number_format((float) $method->price, 2) }}</span>
                                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $method->free_shipping_threshold ? __('Free over BDT :amount', ['amount' => number_format((float) $method->free_shipping_threshold, 2)]) : __('No free threshold') }}
                                                </span>
                                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $method->estimated_days_min || $method->estimated_days_max ? __(':min-:max days', ['min' => $method->estimated_days_min ?? 0, 'max' => $method->estimated_days_max ?? $method->estimated_days_min]) : __('No estimate') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex justify-end gap-2">
                                                    @can('update', $method)
                                                        <x-ui.button size="sm" variant="secondary" wire:click="editMethod({{ $method->id }})">{{ __('Edit') }}</x-ui.button>
                                                        <x-ui.button size="sm" variant="danger" wire:click="deleteMethod({{ $method->id }})" wire:confirm="{{ __('Delete this shipping method?') }}">{{ __('Delete') }}</x-ui.button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('No shipping methods')" :description="__('Add rates for active zones so checkout can resolve delivery options.')" />
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>
