<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Content')"
        :title="__('Navigation menus')"
        :description="__('Manage storefront menu groups, nested items, mega-menu metadata, and links to CMS or catalog content.')"
    >
        <x-slot:actions>
            @can('create', App\Models\NavigationMenu::class)
                <x-ui.button wire:click="createMenu">{{ __('New menu') }}</x-ui.button>
            @endcan
            @can('create', App\Models\NavigationMenuItem::class)
                <x-ui.button variant="secondary" wire:click="createItem">{{ __('New item') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="grid gap-6 xl:grid-cols-[22rem_1fr]">
        <section class="space-y-4">
            <label class="grid gap-2 rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-200">
                {{ __('Search menus') }}
                <input type="search" wire:model.live.debounce.300ms="search" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or slug') }}">
            </label>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900">
                @forelse ($menus as $menu)
                    <button
                        type="button"
                        wire:click="$set('selectedMenuId', '{{ $menu->id }}')"
                        class="flex w-full items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 text-left last:border-b-0 hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-white/5"
                    >
                        <span>
                            <span class="block font-semibold text-zinc-950 dark:text-white">{{ $menu->name }}</span>
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $menu->slug }} / {{ trans_choice(':count item|:count items', $menu->items_count, ['count' => $menu->items_count]) }}</span>
                        </span>
                        <x-ui.badge :tone="$menu->is_active ? 'teal' : 'neutral'">{{ $menu->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                    </button>
                @empty
                    <x-ui.empty-state :title="__('No menus found')" :description="__('Create a menu before adding navigation items.')" />
                @endforelse

                @if ($menus->isNotEmpty())
                    <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $menus->links() }}</div>
                @endif
            </div>
        </section>

        <section class="space-y-6">
            @if ($showMenuForm)
                <form wire:submit="saveMenu" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingMenuId ? __('Edit menu') : __('Create menu') }}</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Name') }}
                            <input wire:model="menuForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="menuForm.name" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Slug') }}
                            <input wire:model="menuForm.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="menuForm.slug" />
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <input wire:model="menuForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                        {{ __('Active') }}
                    </label>

                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="cancelMenu">{{ __('Cancel') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save menu') }}</x-ui.button>
                    </div>
                </form>
            @endif

            @if ($showItemForm)
                <form wire:submit="saveItem" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingItemId ? __('Edit menu item') : __('Create menu item') }}</h2>
                        <x-ui.badge tone="teal">{{ __('Mega menu ready') }}</x-ui.badge>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Menu') }}
                            <select wire:model="itemForm.navigation_menu_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('Choose menu') }}</option>
                                @foreach ($menus as $menu)
                                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="itemForm.navigation_menu_id" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Parent item') }}
                            <select wire:model="itemForm.parent_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('Top level') }}</option>
                                @foreach ($parentOptions as $parentOption)
                                    <option value="{{ $parentOption->id }}">{{ $parentOption->label }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="itemForm.parent_id" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Sort order') }}
                            <input wire:model="itemForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.sort_order" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Label') }}
                            <input wire:model="itemForm.label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.label" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Type') }}
                            <select wire:model.live="itemForm.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="url">{{ __('Custom URL') }}</option>
                                <option value="category">{{ __('Category') }}</option>
                                <option value="collection">{{ __('Collection') }}</option>
                                <option value="page">{{ __('Page') }}</option>
                            </select>
                            <x-ui.input-error for="itemForm.type" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('URL') }}
                            <input wire:model="itemForm.url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.url" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Link target type') }}
                            <select wire:model="itemForm.linkable_type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('No model target') }}</option>
                                <option value="category">{{ __('Category') }}</option>
                                <option value="collection">{{ __('Collection') }}</option>
                                <option value="page">{{ __('Page') }}</option>
                            </select>
                            <x-ui.input-error for="itemForm.linkable_type" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 md:col-span-2">
                            {{ __('Link target') }}
                            <select wire:model="itemForm.linkable_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                <option value="">{{ __('No model target') }}</option>
                                @if (($itemForm['linkable_type'] ?? '') === 'category')
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                @elseif (($itemForm['linkable_type'] ?? '') === 'collection')
                                    @foreach ($collections as $collection)
                                        <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                                    @endforeach
                                @elseif (($itemForm['linkable_type'] ?? '') === 'page')
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}">{{ $page->title }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <x-ui.input-error for="itemForm.linkable_id" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Image path') }}
                            <input wire:model="itemForm.image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.image_path" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Promo title') }}
                            <input wire:model="itemForm.promo_title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.promo_title" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Promo text') }}
                            <input wire:model="itemForm.promo_text" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="itemForm.promo_text" />
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="itemForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Active') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="itemForm.is_mega_menu" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Mega menu') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="itemForm.opens_new_tab" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('New tab') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="itemForm.desktop_visible" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Desktop') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="itemForm.mobile_visible" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Mobile') }}
                        </label>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="cancelItem">{{ __('Cancel') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save item') }}</x-ui.button>
                    </div>
                </form>
            @endif

            @if ($selectedMenu)
                <div class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 border-b border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                        <div>
                            <h2 class="font-semibold text-zinc-950 dark:text-white">{{ $selectedMenu->name }}</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedMenu->slug }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @can('update', $selectedMenu)
                                <x-ui.button size="sm" variant="secondary" wire:click="editMenu({{ $selectedMenu->id }})">{{ __('Edit menu') }}</x-ui.button>
                            @endcan
                            @can('delete', $selectedMenu)
                                <x-ui.button size="sm" variant="danger" wire:click="deleteMenu({{ $selectedMenu->id }})" wire:confirm="{{ __('Delete this menu?') }}">{{ __('Delete') }}</x-ui.button>
                            @endcan
                        </div>
                    </div>

                    @if ($selectedMenu->items->isNotEmpty())
                        <div class="divide-y divide-zinc-200 dark:divide-white/10">
                            @foreach ($selectedMenu->items->whereNull('parent_id') as $item)
                                <div wire:key="navigation-item-{{ $item->id }}" class="p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="font-semibold text-zinc-950 dark:text-white">{{ $item->label }}</div>
                                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $item->url ?: ($item->linkable?->name ?? $item->linkable?->title ?? __('Model target')) }}</div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <x-ui.badge :tone="$item->is_active ? 'teal' : 'neutral'">{{ $item->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                                @if ($item->is_mega_menu)
                                                    <x-ui.badge tone="amber">{{ __('Mega') }}</x-ui.badge>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @can('create', App\Models\NavigationMenuItem::class)
                                                <x-ui.button size="sm" variant="secondary" wire:click="createItem({{ $item->id }})">{{ __('Child') }}</x-ui.button>
                                            @endcan
                                            @can('update', $item)
                                                <x-ui.button size="sm" variant="secondary" wire:click="editItem({{ $item->id }})">{{ __('Edit') }}</x-ui.button>
                                            @endcan
                                            @can('delete', $item)
                                                <x-ui.button size="sm" variant="danger" wire:click="deleteItem({{ $item->id }})" wire:confirm="{{ __('Delete this item?') }}">{{ __('Delete') }}</x-ui.button>
                                            @endcan
                                        </div>
                                    </div>

                                    @if ($item->children->isNotEmpty())
                                        <div class="mt-4 grid gap-2 border-l border-zinc-200 pl-4 dark:border-white/10">
                                            @foreach ($item->children->sortBy([['sort_order', 'asc'], ['label', 'asc']]) as $child)
                                                <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-white/5">
                                                    <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $child->label }}</span>
                                                    <div class="flex gap-2">
                                                        @can('update', $child)
                                                            <x-ui.button size="sm" variant="secondary" wire:click="editItem({{ $child->id }})">{{ __('Edit') }}</x-ui.button>
                                                        @endcan
                                                        @can('delete', $child)
                                                            <x-ui.button size="sm" variant="danger" wire:click="deleteItem({{ $child->id }})" wire:confirm="{{ __('Delete this item?') }}">{{ __('Delete') }}</x-ui.button>
                                                        @endcan
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state :title="__('No menu items found')" :description="__('Add the first item to make this menu render on the storefront.')" />
                    @endif
                </div>
            @else
                <x-ui.empty-state :title="__('Select or create a menu')" :description="__('Menus and their nested items will appear here.')" />
            @endif
        </section>
    </div>
</section>
