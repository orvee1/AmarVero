<?php

namespace App\Livewire\Admin\Content;

use App\Livewire\Admin\Content\Concerns\InteractsWithContentForms;
use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\SocialLink;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FooterContentIndex extends Component
{
    use InteractsWithContentForms;

    public ?int $editingSectionId = null;

    public ?int $editingLinkId = null;

    public ?int $editingSocialId = null;

    /**
     * @var array<string, mixed>
     */
    public array $sectionForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $linkForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $socialForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', FooterSection::class);
        Gate::authorize('viewAny', SocialLink::class);

        $this->resetSectionForm();
        $this->resetLinkForm();
        $this->resetSocialForm();
    }

    public function createSection(): void
    {
        Gate::authorize('create', FooterSection::class);

        $this->resetSectionForm();
    }

    public function createLink(): void
    {
        Gate::authorize('create', FooterLink::class);

        $this->resetLinkForm();
    }

    public function createSocial(): void
    {
        Gate::authorize('create', SocialLink::class);

        $this->resetSocialForm();
    }

    public function editSection(int $sectionId): void
    {
        $section = FooterSection::query()->findOrFail($sectionId);

        Gate::authorize('update', $section);

        $this->editingSectionId = $section->id;
        $this->sectionForm = [
            'title' => $section->title,
            'is_active' => $section->is_active,
            'sort_order' => $section->sort_order,
        ];
    }

    public function saveSection(): void
    {
        $section = $this->editingSectionId ? FooterSection::query()->findOrFail($this->editingSectionId) : new FooterSection;

        Gate::authorize($section->exists ? 'update' : 'create', $section->exists ? $section : FooterSection::class);

        $validated = $this->validate($this->sectionRules())['sectionForm'];

        $section->fill([
            'title' => $this->nullableString($validated['title']),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetSectionForm();

        Flux::toast(variant: 'success', text: __('Footer section saved.'));
    }

    public function editLink(int $linkId): void
    {
        $link = FooterLink::query()->findOrFail($linkId);

        Gate::authorize('update', $link);

        $this->editingLinkId = $link->id;
        $this->linkForm = [
            'footer_section_id' => $link->footer_section_id,
            'label' => $link->label,
            'url' => $link->url,
            'opens_new_tab' => $link->opens_new_tab,
            'is_active' => $link->is_active,
            'sort_order' => $link->sort_order,
        ];
    }

    public function saveLink(): void
    {
        $link = $this->editingLinkId ? FooterLink::query()->findOrFail($this->editingLinkId) : new FooterLink;

        Gate::authorize($link->exists ? 'update' : 'create', $link->exists ? $link : FooterLink::class);

        $validated = $this->validate($this->linkRules())['linkForm'];

        $link->fill([
            'footer_section_id' => (int) $validated['footer_section_id'],
            'label' => $this->nullableString($validated['label']),
            'url' => $this->nullableString($validated['url']),
            'opens_new_tab' => $this->booleanValue($validated['opens_new_tab'] ?? false),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetLinkForm();

        Flux::toast(variant: 'success', text: __('Footer link saved.'));
    }

    public function editSocial(int $socialId): void
    {
        $social = SocialLink::query()->findOrFail($socialId);

        Gate::authorize('update', $social);

        $this->editingSocialId = $social->id;
        $this->socialForm = [
            'label' => $social->label,
            'platform' => $social->platform,
            'url' => $social->url,
            'is_active' => $social->is_active,
            'sort_order' => $social->sort_order,
        ];
    }

    public function saveSocial(): void
    {
        $social = $this->editingSocialId ? SocialLink::query()->findOrFail($this->editingSocialId) : new SocialLink;

        Gate::authorize($social->exists ? 'update' : 'create', $social->exists ? $social : SocialLink::class);

        $validated = $this->validate($this->socialRules())['socialForm'];

        $social->fill([
            'label' => $this->nullableString($validated['label']),
            'platform' => $this->nullableString($validated['platform']),
            'url' => $this->nullableString($validated['url']),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->resetSocialForm();

        Flux::toast(variant: 'success', text: __('Social link saved.'));
    }

    public function deleteSection(int $sectionId): void
    {
        $section = FooterSection::query()->findOrFail($sectionId);

        Gate::authorize('delete', $section);

        $section->delete();
        $this->resetSectionForm();

        Flux::toast(variant: 'success', text: __('Footer section deleted.'));
    }

    public function deleteLink(int $linkId): void
    {
        $link = FooterLink::query()->findOrFail($linkId);

        Gate::authorize('delete', $link);

        $link->delete();
        $this->resetLinkForm();

        Flux::toast(variant: 'success', text: __('Footer link deleted.'));
    }

    public function deleteSocial(int $socialId): void
    {
        $social = SocialLink::query()->findOrFail($socialId);

        Gate::authorize('delete', $social);

        $social->delete();
        $this->resetSocialForm();

        Flux::toast(variant: 'success', text: __('Social link deleted.'));
    }

    public function render(): View
    {
        return view('livewire.admin.content.footer-content-index', [
            'footerSections' => FooterSection::query()
                ->with(['links' => fn ($query) => $query->orderBy('sort_order')->orderBy('label')])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
            'socialLinks' => SocialLink::query()->orderBy('sort_order')->orderBy('platform')->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Footer content'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Content') => null,
                __('Footer') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function sectionRules(): array
    {
        return [
            'sectionForm.title' => ['required', 'string', 'max:255'],
            'sectionForm.is_active' => ['boolean'],
            'sectionForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function linkRules(): array
    {
        return [
            'linkForm.footer_section_id' => ['required', 'integer', 'exists:footer_sections,id'],
            'linkForm.label' => ['required', 'string', 'max:255'],
            'linkForm.url' => ['required', 'string', 'max:255'],
            'linkForm.opens_new_tab' => ['boolean'],
            'linkForm.is_active' => ['boolean'],
            'linkForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function socialRules(): array
    {
        return [
            'socialForm.label' => ['required', 'string', 'max:255'],
            'socialForm.platform' => ['required', 'string', 'max:80'],
            'socialForm.url' => ['required', 'string', 'max:255'],
            'socialForm.is_active' => ['boolean'],
            'socialForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function resetSectionForm(): void
    {
        $this->editingSectionId = null;
        $this->sectionForm = ['title' => '', 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }

    protected function resetLinkForm(): void
    {
        $this->editingLinkId = null;
        $this->linkForm = ['footer_section_id' => '', 'label' => '', 'url' => '', 'opens_new_tab' => false, 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }

    protected function resetSocialForm(): void
    {
        $this->editingSocialId = null;
        $this->socialForm = ['label' => '', 'platform' => '', 'url' => '', 'is_active' => true, 'sort_order' => 0];
        $this->resetValidation();
    }
}
