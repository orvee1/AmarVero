<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Content')"
        :title="__('Announcement bars')"
        :description="__('Manage scheduled top-of-site messages, promotion links, and priority ordering for the storefront.')"
    >
        <x-slot:actions>
            @can('create', App\Models\AnnouncementBar::class)
                <x-ui.button wire:click="create">{{ __('New announcement') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search announcements') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or message') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Status') }}
            <select wire:model.live="statusFilter" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="all">{{ __('All statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit announcement') : __('Create announcement') }}</h2>
                <x-ui.badge tone="teal">{{ __('Scheduled') }}</x-ui.badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Internal name') }}
                    <input wire:model="form.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.name" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model="form.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="form.status" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">
                    {{ __('Message') }}
                    <input wire:model="form.message" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.message" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Link label') }}
                    <input wire:model="form.link_label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.link_label" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Link URL') }}
                    <input wire:model="form.link_url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.link_url" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Starts at') }}
                    <input wire:model="form.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.starts_at" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Ends at') }}
                    <input wire:model="form.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.ends_at" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="form.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sort_order" />
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save announcement') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($announcementBars->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Announcement') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Schedule') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($announcementBars as $announcementBar)
                            <tr wire:key="announcement-bar-{{ $announcementBar->id }}">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $announcementBar->name }}</div>
                                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $announcementBar->message }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $announcementBar->link_label ?: __('No link') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <span class="block">{{ $announcementBar->starts_at?->format('M j, Y H:i') ?? __('No start') }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $announcementBar->ends_at?->format('M j, Y H:i') ?? __('No end') }}</span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <x-ui.badge :tone="$announcementBar->status === App\Enums\ContentStatus::Published ? 'teal' : ($announcementBar->status === App\Enums\ContentStatus::Scheduled ? 'amber' : 'neutral')">
                                        {{ str($announcementBar->status->value)->title() }}
                                    </x-ui.badge>
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Order :order', ['order' => $announcementBar->sort_order]) }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $announcementBar)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $announcementBar->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $announcementBar)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $announcementBar->id }})" wire:confirm="{{ __('Delete this announcement?') }}">{{ __('Delete') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $announcementBars->links() }}
            </div>
        @else
            <x-ui.empty-state
                :title="__('No announcement bars found')"
                :description="__('Create a scheduled message or adjust your filters.')"
            />
        @endif
    </div>
</section>
