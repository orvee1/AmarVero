<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Content')"
        :title="__('Homepage content')"
        :description="__('Control homepage hero slides, scheduled content sections, and promotional banners from database-backed records.')"
    >
        <x-slot:actions>
            @can('create', App\Models\HeroSlide::class)
                <x-ui.button wire:click="createSlide">{{ __('New slide') }}</x-ui.button>
            @endcan
            @can('create', App\Models\HomepageSection::class)
                <x-ui.button variant="secondary" wire:click="createSection">{{ __('New section') }}</x-ui.button>
            @endcan
            @can('create', App\Models\PromotionalBanner::class)
                <x-ui.button variant="secondary" wire:click="createBanner">{{ __('New banner') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    @if ($showSlideForm)
        <form wire:submit="saveSlide" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingSlideId ? __('Edit hero slide') : __('Create hero slide') }}</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">
                    {{ __('Title') }}
                    <input wire:model="slideForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.title" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model="slideForm.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="slideForm.status" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-3 dark:text-zinc-200">
                    {{ __('Subtitle') }}
                    <input wire:model="slideForm.subtitle" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.subtitle" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Desktop image path') }}
                    <input wire:model="slideForm.image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.image_path" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Mobile image path') }}
                    <input wire:model="slideForm.mobile_image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.mobile_image_path" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="slideForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.sort_order" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('CTA label') }}
                    <input wire:model="slideForm.cta_label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.cta_label" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('CTA URL') }}
                    <input wire:model="slideForm.cta_url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.cta_url" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Starts at') }}
                    <input wire:model="slideForm.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.starts_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Ends at') }}
                    <input wire:model="slideForm.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="slideForm.ends_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-3 dark:text-zinc-200">
                    {{ __('Meta key-value rows') }}
                    <textarea wire:model="slideForm.meta_text" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('image_alt: Sneaker in studio') }}"></textarea>
                    <x-ui.input-error for="slideForm.meta_text" />
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancelSlide">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save slide') }}</x-ui.button>
            </div>
        </form>
    @endif

    @if ($showSectionForm)
        <form wire:submit="saveSection" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingSectionId ? __('Edit homepage section') : __('Create homepage section') }}</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Internal name') }}
                    <input wire:model="sectionForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.name" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Type') }}
                    <select wire:model="sectionForm.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($sectionTypes as $type)
                            <option value="{{ $type }}">{{ str($type)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="sectionForm.type" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model="sectionForm.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="sectionForm.status" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Public title') }}
                    <input wire:model="sectionForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.title" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Public subtitle') }}
                    <input wire:model="sectionForm.subtitle" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.subtitle" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="sectionForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.sort_order" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Starts at') }}
                    <input wire:model="sectionForm.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.starts_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Ends at') }}
                    <input wire:model="sectionForm.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="sectionForm.ends_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-3 dark:text-zinc-200">
                    {{ __('Content key-value rows') }}
                    <textarea wire:model="sectionForm.content_text" rows="5" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('description: Lightweight daily comfort') }}"></textarea>
                    <x-ui.input-error for="sectionForm.content_text" />
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancelSection">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save section') }}</x-ui.button>
            </div>
        </form>
    @endif

    @if ($showBannerForm)
        <form wire:submit="saveBanner" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingBannerId ? __('Edit promotional banner') : __('Create promotional banner') }}</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Internal name') }}
                    <input wire:model="bannerForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.name" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Placement') }}
                    <input wire:model="bannerForm.placement" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.placement" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Status') }}
                    <select wire:model="bannerForm.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                        @endforeach
                    </select>
                    <x-ui.input-error for="bannerForm.status" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Title') }}
                    <input wire:model="bannerForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.title" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Subtitle') }}
                    <input wire:model="bannerForm.subtitle" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.subtitle" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Sort order') }}
                    <input wire:model="bannerForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.sort_order" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Desktop image path') }}
                    <input wire:model="bannerForm.image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.image_path" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Mobile image path') }}
                    <input wire:model="bannerForm.mobile_image_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.mobile_image_path" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('CTA label') }}
                    <input wire:model="bannerForm.cta_label" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.cta_label" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('CTA URL') }}
                    <input wire:model="bannerForm.cta_url" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.cta_url" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Starts at') }}
                    <input wire:model="bannerForm.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.starts_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Ends at') }}
                    <input wire:model="bannerForm.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    <x-ui.input-error for="bannerForm.ends_at" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-3 dark:text-zinc-200">
                    {{ __('Meta key-value rows') }}
                    <textarea wire:model="bannerForm.meta_text" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                    <x-ui.input-error for="bannerForm.meta_text" />
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="cancelBanner">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('Save banner') }}</x-ui.button>
            </div>
        </form>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Hero slides') }}</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($slides as $slide)
                    <article class="p-4">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $slide->title }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $slide->image_path }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.badge :tone="$slide->status === App\Enums\ContentStatus::Published ? 'teal' : ($slide->status === App\Enums\ContentStatus::Scheduled ? 'amber' : 'neutral')">{{ str($slide->status->value)->title() }}</x-ui.badge>
                            <x-ui.badge>{{ __('Order :order', ['order' => $slide->sort_order]) }}</x-ui.badge>
                        </div>
                        <div class="mt-4 flex gap-2">
                            @can('update', $slide)
                                <x-ui.button size="sm" variant="secondary" wire:click="editSlide({{ $slide->id }})">{{ __('Edit') }}</x-ui.button>
                            @endcan
                            @can('delete', $slide)
                                <x-ui.button size="sm" variant="danger" wire:click="deleteSlide({{ $slide->id }})" wire:confirm="{{ __('Delete this slide?') }}">{{ __('Delete') }}</x-ui.button>
                            @endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No hero slides')" :description="__('Create a slide to replace the fallback storefront hero.')" />
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Homepage sections') }}</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($sections as $section)
                    <article class="p-4">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $section->name }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ str($section->type)->replace('_', ' ')->title() }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.badge :tone="$section->status === App\Enums\ContentStatus::Published ? 'teal' : ($section->status === App\Enums\ContentStatus::Scheduled ? 'amber' : 'neutral')">{{ str($section->status->value)->title() }}</x-ui.badge>
                            <x-ui.badge>{{ __('Order :order', ['order' => $section->sort_order]) }}</x-ui.badge>
                        </div>
                        <div class="mt-4 flex gap-2">
                            @can('update', $section)
                                <x-ui.button size="sm" variant="secondary" wire:click="editSection({{ $section->id }})">{{ __('Edit') }}</x-ui.button>
                            @endcan
                            @can('delete', $section)
                                <x-ui.button size="sm" variant="danger" wire:click="deleteSection({{ $section->id }})" wire:confirm="{{ __('Delete this section?') }}">{{ __('Delete') }}</x-ui.button>
                            @endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No homepage sections')" :description="__('Create database-controlled sections for the storefront.')" />
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Promotional banners') }}</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($banners as $banner)
                    <article class="p-4">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $banner->name }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $banner->placement }} / {{ $banner->image_path }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.badge :tone="$banner->status === App\Enums\ContentStatus::Published ? 'teal' : ($banner->status === App\Enums\ContentStatus::Scheduled ? 'amber' : 'neutral')">{{ str($banner->status->value)->title() }}</x-ui.badge>
                            <x-ui.badge>{{ __('Order :order', ['order' => $banner->sort_order]) }}</x-ui.badge>
                        </div>
                        <div class="mt-4 flex gap-2">
                            @can('update', $banner)
                                <x-ui.button size="sm" variant="secondary" wire:click="editBanner({{ $banner->id }})">{{ __('Edit') }}</x-ui.button>
                            @endcan
                            @can('delete', $banner)
                                <x-ui.button size="sm" variant="danger" wire:click="deleteBanner({{ $banner->id }})" wire:confirm="{{ __('Delete this banner?') }}">{{ __('Delete') }}</x-ui.button>
                            @endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No promotional banners')" :description="__('Add a banner for campaigns and homepage placements.')" />
                @endforelse
            </div>
        </section>
    </div>
</section>
