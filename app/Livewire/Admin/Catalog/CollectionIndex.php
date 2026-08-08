<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\ProductCollection;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class CollectionIndex extends Component
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
        Gate::authorize('viewAny', ProductCollection::class);

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
        Gate::authorize('create', ProductCollection::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $collectionId): void
    {
        $collection = ProductCollection::query()->findOrFail($collectionId);

        Gate::authorize('update', $collection);

        $this->editingId = $collection->id;
        $this->form = [
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'image_path' => $collection->image_path,
            'is_active' => $collection->is_active,
            'is_featured' => $collection->is_featured,
            'starts_at' => $this->dateTimeInput($collection->starts_at),
            'ends_at' => $this->dateTimeInput($collection->ends_at),
            'sort_order' => $collection->sort_order,
            'seo_title' => $collection->seo_title,
            'seo_description' => $collection->seo_description,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $collection = $this->editingId
            ? ProductCollection::query()->findOrFail($this->editingId)
            : new ProductCollection;

        Gate::authorize($collection->exists ? 'update' : 'create', $collection->exists ? $collection : ProductCollection::class);

        $validated = $this->validate($this->rules())['form'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        $collection->fill([
            'name' => $this->nullableString($validated['name']),
            'slug' => $validated['slug'],
            'description' => $this->nullableString($validated['description'] ?? null),
            'image_path' => $this->nullableString($validated['image_path'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'is_featured' => $this->booleanValue($validated['is_featured'] ?? false),
            'starts_at' => $this->nullableDateTime($validated['starts_at'] ?? null),
            'ends_at' => $this->nullableDateTime($validated['ends_at'] ?? null),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
            'seo_title' => $this->nullableString($validated['seo_title'] ?? null),
            'seo_description' => $this->nullableString($validated['seo_description'] ?? null),
        ])->save();

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Collection saved.'));
    }

    public function delete(int $collectionId): void
    {
        $collection = ProductCollection::query()->findOrFail($collectionId);

        Gate::authorize('delete', $collection);

        $collection->delete();

        if ($this->editingId === $collectionId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Collection deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProductCollection::class);

        $collections = ProductCollection::query()
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

        return view('livewire.admin.catalog.collection-index', [
            'collections' => $collections,
        ])->layout('components.layouts.admin', [
            'title' => __('Collections'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Collections') => null,
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
                Rule::unique('product_collections', 'slug')->ignore($this->editingId),
            ],
            'form.description' => ['nullable', 'string'],
            'form.image_path' => ['nullable', 'string', 'max:255'],
            'form.is_active' => ['boolean'],
            'form.is_featured' => ['boolean'],
            'form.starts_at' => ['nullable', 'date'],
            'form.ends_at' => ['nullable', 'date', 'after_or_equal:form.starts_at'],
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
            'image_path' => '',
            'is_active' => true,
            'is_featured' => false,
            'starts_at' => '',
            'ends_at' => '',
            'sort_order' => 0,
            'seo_title' => '',
            'seo_description' => '',
        ];

        $this->resetValidation();
    }
}
