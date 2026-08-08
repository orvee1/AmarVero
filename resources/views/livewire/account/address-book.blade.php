<section class="w-full space-y-8">
    <x-ui.section-heading
        :title="__('Address book')"
        :description="__('Save delivery and billing destinations for faster checkout.')"
    >
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" wire:click="create">{{ __('New address') }}</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="subtle" wire:navigate>{{ __('Dashboard') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    @if (session('status'))
        <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[24rem_1fr] xl:items-start">
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div>
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">
                    {{ $editingAddressId ? __('Edit address') : __('Add address') }}
                </h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Defaults are used to prefill checkout where possible.') }}</p>
            </div>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Type') }}
                <select wire:model="form.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    @foreach ($addressTypes as $type)
                        <option value="{{ $type->value }}">{{ str($type->value)->title() }}</option>
                    @endforeach
                </select>
                <x-ui.input-error for="form.type" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Name') }}
                <input wire:model="form.name" type="text" autocomplete="name" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.name" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Phone') }}
                <input wire:model="form.phone" type="tel" autocomplete="tel" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.phone" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Address line 1') }}
                <input wire:model="form.line_one" type="text" autocomplete="address-line1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.line_one" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Address line 2') }}
                <input wire:model="form.line_two" type="text" autocomplete="address-line2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.line_two" />
            </label>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Area') }}
                    <input wire:model="form.area" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.area" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('City') }}
                    <input wire:model="form.city" type="text" autocomplete="address-level2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.city" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Region') }}
                    <input wire:model="form.region" type="text" autocomplete="address-level1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.region" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Postal code') }}
                    <input wire:model="form.postal_code" type="text" autocomplete="postal-code" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.postal_code" />
                </label>
            </div>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Country code') }}
                <input wire:model="form.country_code" type="text" maxlength="2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm uppercase text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.country_code" />
            </label>

            <div class="grid gap-3 text-sm text-zinc-700 dark:text-zinc-200">
                <label class="flex items-center gap-3">
                    <input wire:model="form.is_default_shipping" type="checkbox" class="rounded border-zinc-300">
                    <span>{{ __('Default shipping') }}</span>
                </label>
                <label class="flex items-center gap-3">
                    <input wire:model="form.is_default_billing" type="checkbox" class="rounded border-zinc-300">
                    <span>{{ __('Default billing') }}</span>
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                @if ($editingAddressId)
                    <x-ui.button type="button" variant="secondary" wire:click="create">{{ __('Cancel') }}</x-ui.button>
                @endif
                <x-ui.button type="submit">{{ __('Save address') }}</x-ui.button>
            </div>
        </form>

        <section class="space-y-4">
            @forelse ($addresses as $address)
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $address->name }}</h2>
                                <x-ui.badge>{{ str($address->type->value)->title() }}</x-ui.badge>
                                @if ($address->is_default_shipping)
                                    <x-ui.badge tone="teal">{{ __('Default shipping') }}</x-ui.badge>
                                @endif
                                @if ($address->is_default_billing)
                                    <x-ui.badge tone="amber">{{ __('Default billing') }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                {{ $address->line_one }}@if ($address->line_two), {{ $address->line_two }}@endif<br>
                                @if ($address->area){{ $address->area }}, @endif{{ $address->city }}@if ($address->region), {{ $address->region }}@endif @if ($address->postal_code){{ $address->postal_code }}@endif<br>
                                {{ $address->country_code }} · {{ $address->phone }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <x-ui.button type="button" size="sm" variant="secondary" wire:click="edit({{ $address->id }})">{{ __('Edit') }}</x-ui.button>
                            @unless ($address->is_default_shipping)
                                <x-ui.button type="button" size="sm" variant="subtle" wire:click="setDefaultShipping({{ $address->id }})">{{ __('Ship default') }}</x-ui.button>
                            @endunless
                            @unless ($address->is_default_billing)
                                <x-ui.button type="button" size="sm" variant="subtle" wire:click="setDefaultBilling({{ $address->id }})">{{ __('Bill default') }}</x-ui.button>
                            @endunless
                            <x-ui.button type="button" size="sm" variant="danger" wire:click="delete({{ $address->id }})" wire:confirm="{{ __('Remove this address?') }}">{{ __('Delete') }}</x-ui.button>
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty-state
                    :title="__('No saved addresses')"
                    :description="__('Add a delivery address to make checkout faster.')"
                />
            @endforelse
        </section>
    </div>
</section>
