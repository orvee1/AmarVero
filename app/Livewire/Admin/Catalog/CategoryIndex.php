<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryIndex extends Component
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
        Gate::authorize('viewAny', Category::class);

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
        Gate::authorize('create', Category::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        Gate::authorize('update', $category);

        $this->editingId = $category->id;
        $this->form = [
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image_path' => $category->image_path,
            'is_active' => $category->is_active,
            'is_featured' => $category->is_featured,
            'sort_order' => $category->sort_order,
            'seo_title' => $category->seo_title,
            'seo_description' => $category->seo_description,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $category = $this->editingId
            ? Category::query()->findOrFail($this->editingId)
            : new Category;

        Gate::authorize($category->exists ? 'update' : 'create', $category->exists ? $category : Category::class);

        $validated = $this->validate($this->rules())['form'];
        $parentId = $this->nullableInteger($validated['parent_id'] ?? null);

        if ($this->editingId !== null && $parentId === $this->editingId) {
            $this->addError('form.parent_id', __('A category cannot be its own parent.'));

            return;
        }

        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        $category->fill([
            'parent_id' => $parentId,
            'name' => $this->nullableString($validated['name']),
            'slug' => $validated['slug'],
            'description' => $this->nullableString($validated['description'] ?? null),
            'image_path' => $this->nullableString($validated['image_path'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'is_featured' => $this->booleanValue($validated['is_featured'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
            'seo_title' => $this->nullableString($validated['seo_title'] ?? null),
            'seo_description' => $this->nullableString($validated['seo_description'] ?? null),
        ])->save();

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    public function delete(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        Gate::authorize('delete', $category);

        $category->delete();

        if ($this->editingId === $categoryId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['children', 'products'])
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
            ->orderByRaw('parent_id is not null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.catalog.category-index', [
            'categories' => $categories,
            'parentOptions' => Category::query()
                ->when($this->editingId !== null, fn ($query) => $query->whereKeyNot($this->editingId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ])->layout('components.layouts.admin', [
            'title' => __('Categories'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Categories') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function rules(): array
    {
        return [
            'form.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->editingId),
            ],
            'form.description' => ['nullable', 'string'],
            'form.image_path' => ['nullable', 'string', 'max:255'],
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
            'parent_id' => '',
            'name' => '',
            'slug' => '',
            'description' => '',
            'image_path' => '',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'seo_title' => '',
            'seo_description' => '',
        ];

        $this->resetValidation();
    }
}
