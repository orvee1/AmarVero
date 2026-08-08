<?php

namespace App\Livewire\Admin\Content;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Content\Concerns\InteractsWithContentForms;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\PromotionalBanner;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class HomepageContentIndex extends Component
{
    use InteractsWithContentForms;

    public bool $showSlideForm = false;

    public bool $showSectionForm = false;

    public bool $showBannerForm = false;

    public ?int $editingSlideId = null;

    public ?int $editingSectionId = null;

    public ?int $editingBannerId = null;

    /**
     * @var array<string, mixed>
     */
    public array $slideForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $sectionForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $bannerForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', HeroSlide::class);
        Gate::authorize('viewAny', HomepageSection::class);
        Gate::authorize('viewAny', PromotionalBanner::class);

        $this->resetSlideForm();
        $this->resetSectionForm();
        $this->resetBannerForm();
    }

    public function createSlide(): void
    {
        Gate::authorize('create', HeroSlide::class);

        $this->resetSlideForm();
        $this->showSlideForm = true;
    }

    public function editSlide(int $slideId): void
    {
        $slide = HeroSlide::query()->findOrFail($slideId);

        Gate::authorize('update', $slide);

        $this->editingSlideId = $slide->id;
        $this->slideForm = [
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'image_path' => $slide->image_path,
            'mobile_image_path' => $slide->mobile_image_path,
            'cta_label' => $slide->cta_label,
            'cta_url' => $slide->cta_url,
            'status' => $this->contentStatusValue($slide),
            'starts_at' => $this->dateTimeInput($slide->starts_at),
            'ends_at' => $this->dateTimeInput($slide->ends_at),
            'sort_order' => $slide->sort_order,
            'meta_text' => $this->keyValueArrayToText($slide->meta),
        ];
        $this->showSlideForm = true;
    }

    public function saveSlide(): void
    {
        $slide = $this->editingSlideId
            ? HeroSlide::query()->findOrFail($this->editingSlideId)
            : new HeroSlide;

        Gate::authorize($slide->exists ? 'update' : 'create', $slide->exists ? $slide : HeroSlide::class);

        $validated = $this->validate($this->slideRules())['slideForm'];

        $slide->fill(array_merge([
            'title' => $this->nullableString($validated['title']),
            'subtitle' => $this->nullableString($validated['subtitle'] ?? null),
            'image_path' => $this->nullableString($validated['image_path']),
            'mobile_image_path' => $this->nullableString($validated['mobile_image_path'] ?? null),
            'cta_label' => $this->nullableString($validated['cta_label'] ?? null),
            'cta_url' => $this->nullableString($validated['cta_url'] ?? null),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
            'meta' => $this->keyValueTextToArray($validated['meta_text'] ?? null),
        ], $this->scheduledContentFields($validated)))->save();

        $this->resetSlideForm();
        $this->showSlideForm = false;

        Flux::toast(variant: 'success', text: __('Hero slide saved.'));
    }

    public function deleteSlide(int $slideId): void
    {
        $slide = HeroSlide::query()->findOrFail($slideId);

        Gate::authorize('delete', $slide);

        $slide->delete();
        $this->resetSlideForm();
        $this->showSlideForm = false;

        Flux::toast(variant: 'success', text: __('Hero slide deleted.'));
    }

    public function createSection(): void
    {
        Gate::authorize('create', HomepageSection::class);

        $this->resetSectionForm();
        $this->showSectionForm = true;
    }

    public function editSection(int $sectionId): void
    {
        $section = HomepageSection::query()->findOrFail($sectionId);

        Gate::authorize('update', $section);

        $this->editingSectionId = $section->id;
        $this->sectionForm = [
            'name' => $section->name,
            'type' => $section->type,
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content_text' => $this->keyValueArrayToText($section->content),
            'status' => $this->contentStatusValue($section),
            'starts_at' => $this->dateTimeInput($section->starts_at),
            'ends_at' => $this->dateTimeInput($section->ends_at),
            'sort_order' => $section->sort_order,
        ];
        $this->showSectionForm = true;
    }

    public function saveSection(): void
    {
        $section = $this->editingSectionId
            ? HomepageSection::query()->findOrFail($this->editingSectionId)
            : new HomepageSection;

        Gate::authorize($section->exists ? 'update' : 'create', $section->exists ? $section : HomepageSection::class);

        $validated = $this->validate($this->sectionRules())['sectionForm'];

        $section->fill(array_merge([
            'name' => $this->nullableString($validated['name']),
            'type' => $validated['type'],
            'title' => $this->nullableString($validated['title'] ?? null),
            'subtitle' => $this->nullableString($validated['subtitle'] ?? null),
            'content' => $this->keyValueTextToArray($validated['content_text'] ?? null),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ], $this->scheduledContentFields($validated)))->save();

        $this->resetSectionForm();
        $this->showSectionForm = false;

        Flux::toast(variant: 'success', text: __('Homepage section saved.'));
    }

    public function deleteSection(int $sectionId): void
    {
        $section = HomepageSection::query()->findOrFail($sectionId);

        Gate::authorize('delete', $section);

        $section->delete();
        $this->resetSectionForm();
        $this->showSectionForm = false;

        Flux::toast(variant: 'success', text: __('Homepage section deleted.'));
    }

    public function createBanner(): void
    {
        Gate::authorize('create', PromotionalBanner::class);

        $this->resetBannerForm();
        $this->showBannerForm = true;
    }

    public function editBanner(int $bannerId): void
    {
        $banner = PromotionalBanner::query()->findOrFail($bannerId);

        Gate::authorize('update', $banner);

        $this->editingBannerId = $banner->id;
        $this->bannerForm = [
            'name' => $banner->name,
            'placement' => $banner->placement,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'image_path' => $banner->image_path,
            'mobile_image_path' => $banner->mobile_image_path,
            'cta_label' => $banner->cta_label,
            'cta_url' => $banner->cta_url,
            'status' => $this->contentStatusValue($banner),
            'starts_at' => $this->dateTimeInput($banner->starts_at),
            'ends_at' => $this->dateTimeInput($banner->ends_at),
            'sort_order' => $banner->sort_order,
            'meta_text' => $this->keyValueArrayToText($banner->meta),
        ];
        $this->showBannerForm = true;
    }

    public function saveBanner(): void
    {
        $banner = $this->editingBannerId
            ? PromotionalBanner::query()->findOrFail($this->editingBannerId)
            : new PromotionalBanner;

        Gate::authorize($banner->exists ? 'update' : 'create', $banner->exists ? $banner : PromotionalBanner::class);

        $validated = $this->validate($this->bannerRules())['bannerForm'];

        $banner->fill(array_merge([
            'name' => $this->nullableString($validated['name']),
            'placement' => $validated['placement'],
            'title' => $this->nullableString($validated['title'] ?? null),
            'subtitle' => $this->nullableString($validated['subtitle'] ?? null),
            'image_path' => $this->nullableString($validated['image_path']),
            'mobile_image_path' => $this->nullableString($validated['mobile_image_path'] ?? null),
            'cta_label' => $this->nullableString($validated['cta_label'] ?? null),
            'cta_url' => $this->nullableString($validated['cta_url'] ?? null),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
            'meta' => $this->keyValueTextToArray($validated['meta_text'] ?? null),
        ], $this->scheduledContentFields($validated)))->save();

        $this->resetBannerForm();
        $this->showBannerForm = false;

        Flux::toast(variant: 'success', text: __('Promotional banner saved.'));
    }

    public function deleteBanner(int $bannerId): void
    {
        $banner = PromotionalBanner::query()->findOrFail($bannerId);

        Gate::authorize('delete', $banner);

        $banner->delete();
        $this->resetBannerForm();
        $this->showBannerForm = false;

        Flux::toast(variant: 'success', text: __('Promotional banner deleted.'));
    }

    public function cancelSlide(): void
    {
        $this->resetSlideForm();
        $this->showSlideForm = false;
    }

    public function cancelSection(): void
    {
        $this->resetSectionForm();
        $this->showSectionForm = false;
    }

    public function cancelBanner(): void
    {
        $this->resetBannerForm();
        $this->showBannerForm = false;
    }

    public function render(): View
    {
        return view('livewire.admin.content.homepage-content-index', [
            'slides' => HeroSlide::query()->orderBy('sort_order')->latest()->get(),
            'sections' => HomepageSection::query()->orderBy('sort_order')->latest()->get(),
            'banners' => PromotionalBanner::query()->orderBy('placement')->orderBy('sort_order')->latest()->get(),
            'statuses' => ContentStatus::cases(),
            'sectionTypes' => $this->sectionTypes(),
        ])->layout('components.layouts.admin', [
            'title' => __('Homepage content'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Content') => null,
                __('Homepage') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function slideRules(): array
    {
        return [
            'slideForm.title' => ['required', 'string', 'max:255'],
            'slideForm.subtitle' => ['nullable', 'string', 'max:255'],
            'slideForm.image_path' => ['required', 'string', 'max:255'],
            'slideForm.mobile_image_path' => ['nullable', 'string', 'max:255'],
            'slideForm.cta_label' => ['nullable', 'string', 'max:80'],
            'slideForm.cta_url' => ['nullable', 'string', 'max:255'],
            'slideForm.status' => ['required', Rule::in($this->contentStatusValues())],
            'slideForm.starts_at' => ['nullable', 'date'],
            'slideForm.ends_at' => ['nullable', 'date', 'after_or_equal:slideForm.starts_at'],
            'slideForm.sort_order' => ['nullable', 'integer', 'min:0'],
            'slideForm.meta_text' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function sectionRules(): array
    {
        return [
            'sectionForm.name' => ['required', 'string', 'max:255'],
            'sectionForm.type' => ['required', Rule::in($this->sectionTypes())],
            'sectionForm.title' => ['nullable', 'string', 'max:255'],
            'sectionForm.subtitle' => ['nullable', 'string', 'max:255'],
            'sectionForm.content_text' => ['nullable', 'string', 'max:5000'],
            'sectionForm.status' => ['required', Rule::in($this->contentStatusValues())],
            'sectionForm.starts_at' => ['nullable', 'date'],
            'sectionForm.ends_at' => ['nullable', 'date', 'after_or_equal:sectionForm.starts_at'],
            'sectionForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function bannerRules(): array
    {
        return [
            'bannerForm.name' => ['required', 'string', 'max:255'],
            'bannerForm.placement' => ['required', 'string', 'max:80'],
            'bannerForm.title' => ['nullable', 'string', 'max:255'],
            'bannerForm.subtitle' => ['nullable', 'string', 'max:255'],
            'bannerForm.image_path' => ['required', 'string', 'max:255'],
            'bannerForm.mobile_image_path' => ['nullable', 'string', 'max:255'],
            'bannerForm.cta_label' => ['nullable', 'string', 'max:80'],
            'bannerForm.cta_url' => ['nullable', 'string', 'max:255'],
            'bannerForm.status' => ['required', Rule::in($this->contentStatusValues())],
            'bannerForm.starts_at' => ['nullable', 'date'],
            'bannerForm.ends_at' => ['nullable', 'date', 'after_or_equal:bannerForm.starts_at'],
            'bannerForm.sort_order' => ['nullable', 'integer', 'min:0'],
            'bannerForm.meta_text' => ['nullable', 'string', 'max:4000'],
        ];
    }

    protected function resetSlideForm(): void
    {
        $this->editingSlideId = null;
        $this->slideForm = [
            'title' => '',
            'subtitle' => '',
            'image_path' => '',
            'mobile_image_path' => '',
            'cta_label' => '',
            'cta_url' => '',
            'status' => ContentStatus::Draft->value,
            'starts_at' => '',
            'ends_at' => '',
            'sort_order' => 0,
            'meta_text' => '',
        ];

        $this->resetValidation();
    }

    protected function resetSectionForm(): void
    {
        $this->editingSectionId = null;
        $this->sectionForm = [
            'name' => '',
            'type' => 'editorial',
            'title' => '',
            'subtitle' => '',
            'content_text' => '',
            'status' => ContentStatus::Draft->value,
            'starts_at' => '',
            'ends_at' => '',
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    protected function resetBannerForm(): void
    {
        $this->editingBannerId = null;
        $this->bannerForm = [
            'name' => '',
            'placement' => 'home',
            'title' => '',
            'subtitle' => '',
            'image_path' => '',
            'mobile_image_path' => '',
            'cta_label' => '',
            'cta_url' => '',
            'status' => ContentStatus::Draft->value,
            'starts_at' => '',
            'ends_at' => '',
            'sort_order' => 0,
            'meta_text' => '',
        ];

        $this->resetValidation();
    }

    /**
     * @return list<string>
     */
    protected function sectionTypes(): array
    {
        return [
            'featured_categories',
            'gender_tiles',
            'product_carousel',
            'collection_cards',
            'brand_story',
            'testimonials',
            'service_benefits',
            'store_locator',
            'newsletter',
            'editorial',
        ];
    }

    protected function contentStatusValue(HeroSlide|HomepageSection|PromotionalBanner $content): string
    {
        $status = $content->getAttribute('status');

        return $status instanceof ContentStatus ? $status->value : (string) $status;
    }
}
