<?php

namespace App\Livewire\Admin\Content;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Content\Concerns\InteractsWithContentForms;
use App\Models\Faq;
use App\Models\ServiceBenefit;
use App\Models\StaticPage;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ContentLibraryIndex extends Component
{
    use InteractsWithContentForms;

    public string $panel = 'pages';

    public ?int $editingPageId = null;

    public ?int $editingFaqId = null;

    public ?int $editingTestimonialId = null;

    public ?int $editingBenefitId = null;

    public ?int $editingLocationId = null;

    /**
     * @var array<string, mixed>
     */
    public array $pageForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $faqForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $testimonialForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $benefitForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $locationForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', StaticPage::class);
        Gate::authorize('viewAny', Faq::class);
        Gate::authorize('viewAny', Testimonial::class);
        Gate::authorize('viewAny', ServiceBenefit::class);
        Gate::authorize('viewAny', StoreLocation::class);

        $this->resetPageForm();
        $this->resetFaqForm();
        $this->resetTestimonialForm();
        $this->resetBenefitForm();
        $this->resetLocationForm();
    }

    public function createPage(): void
    {
        Gate::authorize('create', StaticPage::class);

        $this->resetPageForm();
        $this->panel = 'pages';
    }

    public function createFaq(): void
    {
        Gate::authorize('create', Faq::class);

        $this->resetFaqForm();
        $this->panel = 'faqs';
    }

    public function createTestimonial(): void
    {
        Gate::authorize('create', Testimonial::class);

        $this->resetTestimonialForm();
        $this->panel = 'testimonials';
    }

    public function createBenefit(): void
    {
        Gate::authorize('create', ServiceBenefit::class);

        $this->resetBenefitForm();
        $this->panel = 'benefits';
    }

    public function createLocation(): void
    {
        Gate::authorize('create', StoreLocation::class);

        $this->resetLocationForm();
        $this->panel = 'locations';
    }

    public function editPage(int $pageId): void
    {
        $page = StaticPage::query()->findOrFail($pageId);

        Gate::authorize('update', $page);

        $this->editingPageId = $page->id;
        $this->pageForm = [
            'title' => $page->title,
            'slug' => $page->slug,
            'body' => $page->body,
            'status' => $this->contentStatusValue($page),
            'published_at' => $this->dateTimeInput($page->published_at),
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
        ];
        $this->panel = 'pages';
    }

    public function savePage(): void
    {
        $page = $this->editingPageId
            ? StaticPage::query()->findOrFail($this->editingPageId)
            : new StaticPage;

        Gate::authorize($page->exists ? 'update' : 'create', $page->exists ? $page : StaticPage::class);

        $validated = $this->validate($this->pageRules())['pageForm'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['title']);

        $status = ContentStatus::from($validated['status']);
        $publishedAt = $this->nullableDateTime($validated['published_at'] ?? null);

        if ($status === ContentStatus::Published && $publishedAt === null) {
            $publishedAt = now()->format('Y-m-d H:i:s');
        }

        if (in_array($status, [ContentStatus::Draft, ContentStatus::Archived], true)) {
            $publishedAt = null;
        }

        $page->fill([
            'title' => $this->nullableString($validated['title']),
            'slug' => $validated['slug'],
            'body' => $this->nullableString($validated['body']),
            'status' => $status,
            'published_at' => $publishedAt,
            'seo_title' => $this->nullableString($validated['seo_title'] ?? null),
            'seo_description' => $this->nullableString($validated['seo_description'] ?? null),
        ])->save();

        $this->resetPageForm();

        Flux::toast(variant: 'success', text: __('Static page saved.'));
    }

    public function deletePage(int $pageId): void
    {
        $page = StaticPage::query()->findOrFail($pageId);

        Gate::authorize('delete', $page);

        $page->delete();
        $this->resetPageForm();

        Flux::toast(variant: 'success', text: __('Static page deleted.'));
    }

    public function editFaq(int $faqId): void
    {
        $faq = Faq::query()->findOrFail($faqId);

        Gate::authorize('update', $faq);

        $this->editingFaqId = $faq->id;
        $this->faqForm = [
            'group' => $faq->group,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'is_active' => $faq->is_active,
            'sort_order' => $faq->sort_order,
        ];
        $this->panel = 'faqs';
    }

    public function saveFaq(): void
    {
        $faq = $this->editingFaqId ? Faq::query()->findOrFail($this->editingFaqId) : new Faq;

        Gate::authorize($faq->exists ? 'update' : 'create', $faq->exists ? $faq : Faq::class);

        $validated = $this->validate($this->faqRules())['faqForm'];

        $faq->fill([
            'group' => $this->nullableString($validated['group'] ?? null),
            'question' => $this->nullableString($validated['question']),
            'answer' => $this->nullableString($validated['answer']),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetFaqForm();

        Flux::toast(variant: 'success', text: __('FAQ saved.'));
    }

    public function deleteFaq(int $faqId): void
    {
        $faq = Faq::query()->findOrFail($faqId);

        Gate::authorize('delete', $faq);

        $faq->delete();
        $this->resetFaqForm();

        Flux::toast(variant: 'success', text: __('FAQ deleted.'));
    }

    public function editTestimonial(int $testimonialId): void
    {
        $testimonial = Testimonial::query()->findOrFail($testimonialId);

        Gate::authorize('update', $testimonial);

        $this->editingTestimonialId = $testimonial->id;
        $this->testimonialForm = [
            'name' => $testimonial->name,
            'role' => $testimonial->role,
            'avatar_path' => $testimonial->avatar_path,
            'quote' => $testimonial->quote,
            'rating' => $testimonial->rating,
            'is_active' => $testimonial->is_active,
            'sort_order' => $testimonial->sort_order,
        ];
        $this->panel = 'testimonials';
    }

    public function saveTestimonial(): void
    {
        $testimonial = $this->editingTestimonialId ? Testimonial::query()->findOrFail($this->editingTestimonialId) : new Testimonial;

        Gate::authorize($testimonial->exists ? 'update' : 'create', $testimonial->exists ? $testimonial : Testimonial::class);

        $validated = $this->validate($this->testimonialRules())['testimonialForm'];

        $testimonial->fill([
            'name' => $this->nullableString($validated['name']),
            'role' => $this->nullableString($validated['role'] ?? null),
            'avatar_path' => $this->nullableString($validated['avatar_path'] ?? null),
            'quote' => $this->nullableString($validated['quote']),
            'rating' => $this->nullableInteger($validated['rating'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetTestimonialForm();

        Flux::toast(variant: 'success', text: __('Testimonial saved.'));
    }

    public function deleteTestimonial(int $testimonialId): void
    {
        $testimonial = Testimonial::query()->findOrFail($testimonialId);

        Gate::authorize('delete', $testimonial);

        $testimonial->delete();
        $this->resetTestimonialForm();

        Flux::toast(variant: 'success', text: __('Testimonial deleted.'));
    }

    public function editBenefit(int $benefitId): void
    {
        $benefit = ServiceBenefit::query()->findOrFail($benefitId);

        Gate::authorize('update', $benefit);

        $this->editingBenefitId = $benefit->id;
        $this->benefitForm = [
            'title' => $benefit->title,
            'subtitle' => $benefit->subtitle,
            'icon' => $benefit->icon,
            'is_active' => $benefit->is_active,
            'sort_order' => $benefit->sort_order,
        ];
        $this->panel = 'benefits';
    }

    public function saveBenefit(): void
    {
        $benefit = $this->editingBenefitId ? ServiceBenefit::query()->findOrFail($this->editingBenefitId) : new ServiceBenefit;

        Gate::authorize($benefit->exists ? 'update' : 'create', $benefit->exists ? $benefit : ServiceBenefit::class);

        $validated = $this->validate($this->benefitRules())['benefitForm'];

        $benefit->fill([
            'title' => $this->nullableString($validated['title']),
            'subtitle' => $this->nullableString($validated['subtitle'] ?? null),
            'icon' => $this->nullableString($validated['icon'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetBenefitForm();

        Flux::toast(variant: 'success', text: __('Service benefit saved.'));
    }

    public function deleteBenefit(int $benefitId): void
    {
        $benefit = ServiceBenefit::query()->findOrFail($benefitId);

        Gate::authorize('delete', $benefit);

        $benefit->delete();
        $this->resetBenefitForm();

        Flux::toast(variant: 'success', text: __('Service benefit deleted.'));
    }

    public function editLocation(int $locationId): void
    {
        $location = StoreLocation::query()->findOrFail($locationId);

        Gate::authorize('update', $location);

        $this->editingLocationId = $location->id;
        $this->locationForm = [
            'name' => $location->name,
            'phone' => $location->phone,
            'email' => $location->email,
            'line_one' => $location->line_one,
            'line_two' => $location->line_two,
            'city' => $location->city,
            'region' => $location->region,
            'postal_code' => $location->postal_code,
            'country_code' => $location->country_code,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'opening_hours_text' => $this->keyValueArrayToText($location->opening_hours),
            'is_active' => $location->is_active,
            'sort_order' => $location->sort_order,
        ];
        $this->panel = 'locations';
    }

    public function saveLocation(): void
    {
        $location = $this->editingLocationId ? StoreLocation::query()->findOrFail($this->editingLocationId) : new StoreLocation;

        Gate::authorize($location->exists ? 'update' : 'create', $location->exists ? $location : StoreLocation::class);

        $validated = $this->validate($this->locationRules())['locationForm'];

        $location->fill([
            'name' => $this->nullableString($validated['name']),
            'phone' => $this->nullableString($validated['phone'] ?? null),
            'email' => $this->nullableString($validated['email'] ?? null),
            'line_one' => $this->nullableString($validated['line_one']),
            'line_two' => $this->nullableString($validated['line_two'] ?? null),
            'city' => $this->nullableString($validated['city']),
            'region' => $this->nullableString($validated['region'] ?? null),
            'postal_code' => $this->nullableString($validated['postal_code'] ?? null),
            'country_code' => strtoupper((string) $validated['country_code']),
            'latitude' => $this->nullableDecimal($validated['latitude'] ?? null),
            'longitude' => $this->nullableDecimal($validated['longitude'] ?? null),
            'opening_hours' => $this->keyValueTextToArray($validated['opening_hours_text'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetLocationForm();

        Flux::toast(variant: 'success', text: __('Store location saved.'));
    }

    public function deleteLocation(int $locationId): void
    {
        $location = StoreLocation::query()->findOrFail($locationId);

        Gate::authorize('delete', $location);

        $location->delete();
        $this->resetLocationForm();

        Flux::toast(variant: 'success', text: __('Store location deleted.'));
    }

    public function render(): View
    {
        return view('livewire.admin.content.content-library-index', [
            'pages' => StaticPage::query()->latest()->get(),
            'faqs' => Faq::query()->orderBy('group')->orderBy('sort_order')->get(),
            'testimonials' => Testimonial::query()->orderBy('sort_order')->latest()->get(),
            'benefits' => ServiceBenefit::query()->orderBy('sort_order')->get(),
            'locations' => StoreLocation::query()->orderBy('sort_order')->get(),
            'statuses' => ContentStatus::cases(),
        ])->layout('components.layouts.admin', [
            'title' => __('Content library'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Content') => null,
                __('Library') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function pageRules(): array
    {
        return [
            'pageForm.title' => ['required', 'string', 'max:255'],
            'pageForm.slug' => ['nullable', 'string', 'max:255', Rule::unique('static_pages', 'slug')->ignore($this->editingPageId)],
            'pageForm.body' => ['required', 'string'],
            'pageForm.status' => ['required', Rule::in($this->contentStatusValues())],
            'pageForm.published_at' => ['nullable', 'date'],
            'pageForm.seo_title' => ['nullable', 'string', 'max:255'],
            'pageForm.seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function faqRules(): array
    {
        return [
            'faqForm.group' => ['nullable', 'string', 'max:80'],
            'faqForm.question' => ['required', 'string', 'max:255'],
            'faqForm.answer' => ['required', 'string'],
            'faqForm.is_active' => ['boolean'],
            'faqForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function testimonialRules(): array
    {
        return [
            'testimonialForm.name' => ['required', 'string', 'max:255'],
            'testimonialForm.role' => ['nullable', 'string', 'max:255'],
            'testimonialForm.avatar_path' => ['nullable', 'string', 'max:255'],
            'testimonialForm.quote' => ['required', 'string'],
            'testimonialForm.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'testimonialForm.is_active' => ['boolean'],
            'testimonialForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function benefitRules(): array
    {
        return [
            'benefitForm.title' => ['required', 'string', 'max:255'],
            'benefitForm.subtitle' => ['nullable', 'string', 'max:255'],
            'benefitForm.icon' => ['nullable', 'string', 'max:80'],
            'benefitForm.is_active' => ['boolean'],
            'benefitForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function locationRules(): array
    {
        return [
            'locationForm.name' => ['required', 'string', 'max:255'],
            'locationForm.phone' => ['nullable', 'string', 'max:80'],
            'locationForm.email' => ['nullable', 'email', 'max:255'],
            'locationForm.line_one' => ['required', 'string', 'max:255'],
            'locationForm.line_two' => ['nullable', 'string', 'max:255'],
            'locationForm.city' => ['required', 'string', 'max:120'],
            'locationForm.region' => ['nullable', 'string', 'max:120'],
            'locationForm.postal_code' => ['nullable', 'string', 'max:40'],
            'locationForm.country_code' => ['required', 'string', 'size:2'],
            'locationForm.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'locationForm.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'locationForm.opening_hours_text' => ['nullable', 'string', 'max:4000'],
            'locationForm.is_active' => ['boolean'],
            'locationForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function resetPageForm(): void
    {
        $this->editingPageId = null;
        $this->pageForm = [
            'title' => '',
            'slug' => '',
            'body' => '',
            'status' => ContentStatus::Draft->value,
            'published_at' => '',
            'seo_title' => '',
            'seo_description' => '',
        ];
        $this->resetValidation();
    }

    protected function resetFaqForm(): void
    {
        $this->editingFaqId = null;
        $this->faqForm = ['group' => '', 'question' => '', 'answer' => '', 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }

    protected function resetTestimonialForm(): void
    {
        $this->editingTestimonialId = null;
        $this->testimonialForm = ['name' => '', 'role' => '', 'avatar_path' => '', 'quote' => '', 'rating' => '', 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }

    protected function resetBenefitForm(): void
    {
        $this->editingBenefitId = null;
        $this->benefitForm = ['title' => '', 'subtitle' => '', 'icon' => '', 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }

    protected function resetLocationForm(): void
    {
        $this->editingLocationId = null;
        $this->locationForm = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'line_one' => '',
            'line_two' => '',
            'city' => '',
            'region' => '',
            'postal_code' => '',
            'country_code' => 'BD',
            'latitude' => '',
            'longitude' => '',
            'opening_hours_text' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];
        $this->resetValidation();
    }

    protected function contentStatusValue(StaticPage $page): string
    {
        $status = $page->getAttribute('status');

        return $status instanceof ContentStatus ? $status->value : (string) $status;
    }
}
