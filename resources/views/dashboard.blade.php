<x-layouts::app :title="__('Account overview')">
    <div class="space-y-8">
        <x-ui.section-heading
            :title="__('Account overview')"
            :description="__('Review the activity currently linked to your Amarvero account.')"
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('profile.edit')" wire:navigate>
                    {{ __('Edit profile') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.section-heading>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($accountStats as $stat)
                <x-ui.stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :description="$stat['description']"
                    :tone="$stat['tone']"
                />
            @endforeach
        </div>

        <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Latest order') }}</h2>

            @if ($latestOrder)
                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Order number') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $latestOrder->order_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ str($latestOrder->status->value)->replace('_', ' ')->title() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $latestOrder->currency_code }} {{ number_format((float) $latestOrder->grand_total, 2) }}</dd>
                    </div>
                </dl>
            @else
                <x-ui.empty-state
                    class="mt-5"
                    :title="__('No orders yet')"
                    :description="__('Orders connected to this account will appear here after checkout.')"
                />
            @endif
        </section>
    </div>
</x-layouts::app>
