<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Catalog')"
        :title="__('Collections')"
        :description="__('Manage campaign-ready product collections with scheduling, featured state, and SEO copy.')"
    >
        <x-slot:actions>
            @can('create', App\Models\ProductCollection::class)
                <x-ui.button wire:click="create">{{ __('New collection') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 sm:grid-cols-[1fr_auto] dark:border-white/10 dark:bg-zinc-900">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Search collections') }}
            <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or slug') }}">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Status') }}
            <select wire:model.live="status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <option value="all">{{ __('All collections') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
                <option value="featured">{{ __('Featured') }}</option>
            </select>
        </label>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingId ? __('Edit collection') : __('Create collection') }}</h2>

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

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 md:col-span-2">
                    {{ __('Description') }}
                    <textarea wire:model="form.description" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="form.description" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Image path') }}
                    <input wire:model="form.image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.image_path" />
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="form.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="form.sort_order" />
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
                    <input wire:model="form.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Active') }}
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input wire:model="form.is_featured" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                    {{ __('Featured') }}
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancel">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save collection') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
        @if ($collections->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Collection') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Schedule') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Products') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                        @foreach ($collections as $collection)
                            <tr wire:key="collection-{{ $collection->id }}">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $collection->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $collection->slug }}</div>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                    <span class="block">{{ $collection->starts_at?->format('M j, Y H:i') ?? __('No start') }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $collection->ends_at?->format('M j, Y H:i') ?? __('No end') }}</span>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format($collection->products_count) }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge :tone="$collection->is_active ? 'teal' : 'neutral'">{{ $collection->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                        @if ($collection->is_featured)
                                            <x-ui.badge tone="amber">{{ __('Featured') }}</x-ui.badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @can('update', $collection)
                                            <x-ui.button size="sm" variant="secondary" wire:click="edit({{ $collection->id }})">{{ __('Edit') }}</x-ui.button>
                                        @endcan

                                        @can('delete', $collection)
                                            <x-ui.button size="sm" variant="danger" wire:click="delete({{ $collection->id }})" wire:confirm="{{ __('Delete this collection?') }}">{{ __('Delete') }}</x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                {{ $collections->links() }}
            </div>
        @else
            <x-ui.empty-state :title="__('No collections found')" :description="__('Create a collection or adjust your filters to see results.')" />
        @endif
    </div>
</section>
