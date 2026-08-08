<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Content')"
        :title="__('Content library')"
        :description="__('Manage CMS pages, FAQs, testimonials, service benefits, and store locations used by the storefront and policy pages.')"
    />

    <div class="flex flex-wrap gap-2">
        @foreach (['pages' => __('Pages'), 'faqs' => __('FAQs'), 'testimonials' => __('Testimonials'), 'benefits' => __('Benefits'), 'locations' => __('Locations')] as $key => $label)
            <x-ui.button type="button" :variant="$panel === $key ? 'primary' : 'secondary'" wire:click="$set('panel', '{{ $key }}')">{{ $label }}</x-ui.button>
        @endforeach
    </div>

    @if ($panel === 'pages')
        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <form wire:submit="savePage" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingPageId ? __('Edit page') : __('Create page') }}</h2>
                    <x-ui.button type="button" size="sm" variant="secondary" wire:click="createPage">{{ __('New') }}</x-ui.button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Title') }}
                        <input wire:model="pageForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="pageForm.title" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Slug') }}
                        <input wire:model="pageForm.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="pageForm.slug" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Status') }}
                        <select wire:model="pageForm.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}">{{ str($status->value)->title() }}</option>
                            @endforeach
                        </select>
                        <x-ui.input-error for="pageForm.status" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Published at') }}
                        <input wire:model="pageForm.published_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="pageForm.published_at" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">
                        {{ __('Body') }}
                        <textarea wire:model="pageForm.body" rows="8" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                        <x-ui.input-error for="pageForm.body" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('SEO title') }}
                        <input wire:model="pageForm.seo_title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="pageForm.seo_title" />
                    </label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('SEO description') }}
                        <textarea wire:model="pageForm.seo_description" rows="2" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                        <x-ui.input-error for="pageForm.seo_description" />
                    </label>
                </div>
                <div class="flex justify-end">
                    <x-ui.button type="submit">{{ __('Save page') }}</x-ui.button>
                </div>
            </form>

            <div class="space-y-3">
                @forelse ($pages as $page)
                    <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $page->title }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $page->slug }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.badge :tone="$page->status === App\Enums\ContentStatus::Published ? 'teal' : 'neutral'">{{ str($page->status->value)->title() }}</x-ui.badge>
                            @can('update', $page)
                                <x-ui.button size="sm" variant="secondary" wire:click="editPage({{ $page->id }})">{{ __('Edit') }}</x-ui.button>
                            @endcan
                            @can('delete', $page)
                                <x-ui.button size="sm" variant="danger" wire:click="deletePage({{ $page->id }})" wire:confirm="{{ __('Delete this page?') }}">{{ __('Delete') }}</x-ui.button>
                            @endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No pages yet')" />
                @endforelse
            </div>
        </div>
    @elseif ($panel === 'faqs')
        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <form wire:submit="saveFaq" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingFaqId ? __('Edit FAQ') : __('Create FAQ') }}</h2>
                    <x-ui.button type="button" size="sm" variant="secondary" wire:click="createFaq">{{ __('New') }}</x-ui.button>
                </div>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Group') }}<input wire:model="faqForm.group" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="faqForm.group" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Question') }}<input wire:model="faqForm.question" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="faqForm.question" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Answer') }}<textarea wire:model="faqForm.answer" rows="5" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea><x-ui.input-error for="faqForm.answer" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Sort order') }}<input wire:model="faqForm.sort_order" type="number" min="0" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="faqForm.sort_order" /></label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="faqForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
                <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save FAQ') }}</x-ui.button></div>
            </form>
            <div class="space-y-3">
                @forelse ($faqs as $faq)
                    <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $faq->question }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $faq->group ?: __('General') }}</div>
                        <div class="mt-3 flex gap-2">
                            <x-ui.button size="sm" variant="secondary" wire:click="editFaq({{ $faq->id }})">{{ __('Edit') }}</x-ui.button>
                            @can('delete', $faq)<x-ui.button size="sm" variant="danger" wire:click="deleteFaq({{ $faq->id }})" wire:confirm="{{ __('Delete this FAQ?') }}">{{ __('Delete') }}</x-ui.button>@endcan
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('No FAQs yet')" />
                @endforelse
            </div>
        </div>
    @elseif ($panel === 'testimonials')
        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <form wire:submit="saveTestimonial" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3"><h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingTestimonialId ? __('Edit testimonial') : __('Create testimonial') }}</h2><x-ui.button type="button" size="sm" variant="secondary" wire:click="createTestimonial">{{ __('New') }}</x-ui.button></div>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Name') }}<input wire:model="testimonialForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="testimonialForm.name" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Role') }}<input wire:model="testimonialForm.role" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="testimonialForm.role" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Quote') }}<textarea wire:model="testimonialForm.quote" rows="5" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea><x-ui.input-error for="testimonialForm.quote" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Rating') }}<input wire:model="testimonialForm.rating" type="number" min="1" max="5" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="testimonialForm.rating" /></label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="testimonialForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
                <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save testimonial') }}</x-ui.button></div>
            </form>
            <div class="space-y-3">@forelse ($testimonials as $testimonial)<article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"><div class="font-semibold text-zinc-950 dark:text-white">{{ $testimonial->name }}</div><p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ str($testimonial->quote)->limit(110) }}</p><div class="mt-3 flex gap-2"><x-ui.button size="sm" variant="secondary" wire:click="editTestimonial({{ $testimonial->id }})">{{ __('Edit') }}</x-ui.button>@can('delete', $testimonial)<x-ui.button size="sm" variant="danger" wire:click="deleteTestimonial({{ $testimonial->id }})" wire:confirm="{{ __('Delete this testimonial?') }}">{{ __('Delete') }}</x-ui.button>@endcan</div></article>@empty<x-ui.empty-state :title="__('No testimonials yet')" />@endforelse</div>
        </div>
    @elseif ($panel === 'benefits')
        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <form wire:submit="saveBenefit" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3"><h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingBenefitId ? __('Edit benefit') : __('Create benefit') }}</h2><x-ui.button type="button" size="sm" variant="secondary" wire:click="createBenefit">{{ __('New') }}</x-ui.button></div>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Title') }}<input wire:model="benefitForm.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="benefitForm.title" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subtitle') }}<input wire:model="benefitForm.subtitle" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="benefitForm.subtitle" /></label>
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Icon') }}<input wire:model="benefitForm.icon" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="benefitForm.icon" /></label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="benefitForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
                <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save benefit') }}</x-ui.button></div>
            </form>
            <div class="space-y-3">@forelse ($benefits as $benefit)<article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"><div class="font-semibold text-zinc-950 dark:text-white">{{ $benefit->title }}</div><div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $benefit->subtitle }}</div><div class="mt-3 flex gap-2"><x-ui.button size="sm" variant="secondary" wire:click="editBenefit({{ $benefit->id }})">{{ __('Edit') }}</x-ui.button>@can('delete', $benefit)<x-ui.button size="sm" variant="danger" wire:click="deleteBenefit({{ $benefit->id }})" wire:confirm="{{ __('Delete this benefit?') }}">{{ __('Delete') }}</x-ui.button>@endcan</div></article>@empty<x-ui.empty-state :title="__('No benefits yet')" />@endforelse</div>
        </div>
    @elseif ($panel === 'locations')
        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <form wire:submit="saveLocation" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3"><h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingLocationId ? __('Edit location') : __('Create location') }}</h2><x-ui.button type="button" size="sm" variant="secondary" wire:click="createLocation">{{ __('New') }}</x-ui.button></div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Name') }}<input wire:model="locationForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.name" /></label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('City') }}<input wire:model="locationForm.city" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.city" /></label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 md:col-span-2 dark:text-zinc-200">{{ __('Address line one') }}<input wire:model="locationForm.line_one" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.line_one" /></label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Phone') }}<input wire:model="locationForm.phone" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.phone" /></label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Email') }}<input wire:model="locationForm.email" type="email" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.email" /></label>
                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Country code') }}<input wire:model="locationForm.country_code" type="text" maxlength="2" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"><x-ui.input-error for="locationForm.country_code" /></label>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200"><input wire:model="locationForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Active') }}</label>
                </div>
                <div class="flex justify-end"><x-ui.button type="submit">{{ __('Save location') }}</x-ui.button></div>
            </form>
            <div class="space-y-3">@forelse ($locations as $location)<article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"><div class="font-semibold text-zinc-950 dark:text-white">{{ $location->name }}</div><div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $location->line_one }}, {{ $location->city }}</div><div class="mt-3 flex gap-2"><x-ui.button size="sm" variant="secondary" wire:click="editLocation({{ $location->id }})">{{ __('Edit') }}</x-ui.button>@can('delete', $location)<x-ui.button size="sm" variant="danger" wire:click="deleteLocation({{ $location->id }})" wire:confirm="{{ __('Delete this location?') }}">{{ __('Delete') }}</x-ui.button>@endcan</div></article>@empty<x-ui.empty-state :title="__('No locations yet')" />@endforelse</div>
        </div>
    @endif
</section>
