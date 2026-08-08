<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\Brand;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class BrandIndex extends Component
{
    use InteractsWithCatalogForms, WithPagination;

    public string $search = '';

    public string $status = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Brand::class);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Brand::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        Gate::authorize('update', $brand);

        $this->editingId = $brand->id;
        $this->form = [
            'name' => $brand->name,
            'slug' => $brand->slug,
            'description' => $brand->description,
            'logo_path' => $brand->logo_path,
            'banner_path' => $brand->banner_path,
            'website_url' => $brand->website_url,
            'is_active' => $brand->is_active,
            'is_featured' => $brand->is_featured,
            'sort_order' => $brand->sort_order,
            'seo_title' => $brand->seo_title,
            'seo_description' => $brand->seo_description,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $brand = $this->editingId
            ? Brand::query()->findOrFail($this->editingId)
            : new Brand;

        Gate::authorize($brand->exists ? 'update' : 'create', $brand->exists ? $brand : Brand::class);

        $validated = $this->validate($this->rules())['form'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        $brand->fill([
            'name' => $this->nullableString($validated['name']),
            'slug' => $validated['slug'],
            'description' => $this->nullableString($validated['description'] ?? null),
            'logo_path' => $this->nullableString($validated['logo_path'] ?? null),
            'banner_path' => $this->nullableString($validated['banner_path'] ?? null),
            'website_url' => $this->nullableString($validated['website_url'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'is_featured' => $this->booleanValue($validated['is_featured'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
            'seo_title' => $this->nullableString($validated['seo_title'] ?? null),
            'seo_description' => $this->nullableString($validated['seo_description'] ?? null),
        ])->save();

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Brand saved.'));
    }

    public function delete(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        Gate::authorize('delete', $brand);

        $brand->delete();

        if ($this->editingId === $brandId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Brand deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Brand::class);

        $brands = Brand::query()
            ->withCount('products')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->status === 'featured', fn ($query) => $query->where('is_featured', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.catalog.brand-index', [
            'brands' => $brands,
        ])->layout('components.layouts.admin', [
            'title' => __('Brands'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Brands') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($this->editingId),
            ],
            'form.description' => ['nullable', 'string'],
            'form.logo_path' => ['nullable', 'string', 'max:255'],
            'form.banner_path' => ['nullable', 'string', 'max:255'],
            'form.website_url' => ['nullable', 'url', 'max:255'],
            'form.is_active' => ['boolean'],
            'form.is_featured' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.seo_title' => ['nullable', 'string', 'max:255'],
            'form.seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'logo_path' => '',
            'banner_path' => '',
            'website_url' => '',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'seo_title' => '',
            'seo_description' => '',
        ];

        $this->resetValidation();
    }
}
