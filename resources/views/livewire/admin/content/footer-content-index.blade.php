<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Content')"
        :title="__('Footer content')"
        :description="__('Manage footer link groups and social links rendered in the storefront layout.')"
    />

    <div class="grid gap-6 xl:grid-cols-3">
        <form wire:submit="saveSection" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingSectionId ? __('Edit footer section') : __('Create footer section') }}</h2>
                <x-ui.button type="button" size="sm" variant="secondary" wire:click="createSection">{{ __('New') }}</x-ui.button>
            </div>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Title') }}
                <input wire:model="sectionForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="sectionForm.title" />
            </label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Sort order') }}
                <input wire:model="sectionForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="sectionForm.sort_order" />
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input wire:model="sectionForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                {{ __('Active') }}
            </label>
            <div class="flex justify-end">
                <x-ui.button type="submit">{{ __('Save section') }}</x-ui.button>
            </div>
        </form>

        <form wire:submit="saveLink" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingLinkId ? __('Edit footer link') : __('Create footer link') }}</h2>
                <x-ui.button type="button" size="sm" variant="secondary" wire:click="createLink">{{ __('New') }}</x-ui.button>
            </div>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Section') }}
                <select wire:model="linkForm.footer_section_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <option value="">{{ __('Choose section') }}</option>
                    @foreach ($footerSections as $section)
                        <option value="{{ $section->id }}">{{ $section->title }}</option>
                    @endforeach
                </select>
                <x-ui.input-error for="linkForm.footer_section_id" />
            </label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Label') }}<input wire:model="linkForm.label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="linkForm.label" /></label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('URL') }}<input wire:model="linkForm.url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="linkForm.url" /></label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Sort order') }}<input wire:model="linkForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="linkForm.sort_order" /></label>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="linkForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="linkForm.opens_new_tab" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('New tab') }}</label>
            </div>
            <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save link') }}</x-ui.button></div>
        </form>

        <form wire:submit="saveSocial" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingSocialId ? __('Edit social link') : __('Create social link') }}</h2>
                <x-ui.button type="button" size="sm" variant="secondary" wire:click="createSocial">{{ __('New') }}</x-ui.button>
            </div>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Label') }}<input wire:model="socialForm.label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="socialForm.label" /></label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Platform') }}<input wire:model="socialForm.platform" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="socialForm.platform" /></label>
            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('URL') }}<input wire:model="socialForm.url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="socialForm.url" /></label>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="socialForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
            <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save social') }}</x-ui.button></div>
        </form>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Footer sections') }}</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($footerSections as $section)
                    <article class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="font-semibold text-zinc-950 dark:text-white">{{ $section->title }}</div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count link|:count links', $section->links->count(), ['count' => $section->links->count()]) }}</div>
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="secondary" wire:click="editSection({{ $section->id }})">{{ __('Edit') }}</x-ui.button>
                                @can('delete', $section)<x-ui.button size="sm" variant="danger" wire:click="deleteSection({{ $section->id }})" wire:confirm="{{ __('Delete this section?') }}">{{ __('Delete') }}</x-ui.button>@endcan
                            </div>
                        </div>
                        <div class="mt-4 grid gap-2">
                            @foreach ($section->links as $link)
                                <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-white/5">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $link->label }}</span>
                                    <div class="flex gap-2">
                                        <x-ui.button size="sm" variant="secondary" wire:click="editLink({{ $link->id }})">{{ __('Edit') }}</x-ui.button>
                                        @can('delete', $link)<x-ui.button size="sm" variant="danger" wire:click="deleteLink({{ $link->id }})" wire:confirm="{{ __('Delete this link?') }}">{{ __('Delete') }}</x-ui.button>@endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No footer sections yet')" />
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Social links') }}</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($socialLinks as $socialLink)
                    <article class="p-4">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $socialLink->label }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $socialLink->platform }}</div>
                        <div class="mt-3 flex gap-2">
                            <x-ui.button size="sm" variant="secondary" wire:click="editSocial({{ $socialLink->id }})">{{ __('Edit') }}</x-ui.button>
                            @can('delete', $socialLink)<x-ui.button size="sm" variant="danger" wire:click="deleteSocial({{ $socialLink->id }})" wire:confirm="{{ __('Delete this social link?') }}">{{ __('Delete') }}</x-ui.button>@endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No social links yet')" />
                @endforelse
            </div>
        </section>
    </div>
</section>
