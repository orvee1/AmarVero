<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SizeGuide;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class SizeGuideIndex extends Component
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
        Gate::authorize('viewAny', SizeGuide::class);

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
        Gate::authorize('create', SizeGuide::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $sizeGuideId): void
    {
        $sizeGuide = SizeGuide::query()
            ->with('products')
            ->findOrFail($sizeGuideId);

        Gate::authorize('update', $sizeGuide);

        $this->editingId = $sizeGuide->id;
        $this->form = [
            'brand_id' => $sizeGuide->brand_id,
            'category_id' => $sizeGuide->category_id,
            'name' => $sizeGuide->name,
            'slug' => $sizeGuide->slug,
            'content' => $sizeGuide->content,
            'measurements_text' => $this->measurementsText($sizeGuide->measurements),
            'is_active' => $sizeGuide->is_active,
            'product_ids' => $sizeGuide->products->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $sizeGuide = $this->editingId
            ? SizeGuide::query()->findOrFail($this->editingId)
            : new SizeGuide;

        Gate::authorize($sizeGuide->exists ? 'update' : 'create', $sizeGuide->exists ? $sizeGuide : SizeGuide::class);

        $validated = $this->validate($this->rules())['form'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        DB::transaction(function () use ($sizeGuide, $validated): void {
            $sizeGuide->fill([
                'brand_id' => $this->nullableInteger($validated['brand_id'] ?? null),
                'category_id' => $this->nullableInteger($validated['category_id'] ?? null),
                'name' => $this->nullableString($validated['name']),
                'slug' => $validated['slug'],
                'content' => $this->nullableString($validated['content'] ?? null),
                'measurements' => $this->measurementsArray($validated['measurements_text'] ?? null),
                'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            ])->save();

            $sizeGuide->products()->sync($this->integerIds($validated['product_ids'] ?? []));
        });

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Size guide saved.'));
    }

    public function delete(int $sizeGuideId): void
    {
        $sizeGuide = SizeGuide::query()->findOrFail($sizeGuideId);

        Gate::authorize('delete', $sizeGuide);

        $sizeGuide->delete();

        if ($this->editingId === $sizeGuideId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Size guide deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', SizeGuide::class);

        $sizeGuides = SizeGuide::query()
            ->with(['brand', 'category'])
            ->withCount('products')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.catalog.size-guide-index', [
            'sizeGuides' => $sizeGuides,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'base_sku']),
        ])->layout('components.layouts.admin', [
            'title' => __('Size guides'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Size guides') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function rules(): array
    {
        return [
            'form.brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('size_guides', 'slug')->ignore($this->editingId),
            ],
            'form.content' => ['nullable', 'string'],
            'form.measurements_text' => ['nullable', 'string', 'max:4000'],
            'form.is_active' => ['boolean'],
            'form.product_ids' => ['array'],
            'form.product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'brand_id' => '',
            'category_id' => '',
            'name' => '',
            'slug' => '',
            'content' => '',
            'measurements_text' => '',
            'is_active' => true,
            'product_ids' => [],
        ];

        $this->resetValidation();
    }

    /**
     * @return list<array{label: string, measurement: string}>
     */
    protected function measurementsArray(mixed $value): array
    {
        $text = $this->nullableString($value);

        if ($text === null) {
            return [];
        }

        $measurements = collect(preg_split('/\R/', $text) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): array {
                $parts = array_map('trim', explode(':', $line, 2));

                return [
                    'label' => $parts[0],
                    'measurement' => $parts[1] ?? '',
                ];
            })
            ->values()
            ->all();

        return array_values($measurements);
    }

    protected function measurementsText(mixed $measurements): string
    {
        if (! is_array($measurements)) {
            return '';
        }

        return collect($measurements)
            ->map(function (mixed $row): ?string {
                if (! is_array($row)) {
                    return null;
                }

                $label = $this->nullableString($row['label'] ?? null);
                $measurement = $this->nullableString($row['measurement'] ?? null);

                if ($label === null) {
                    return null;
                }

                return $measurement === null ? $label : $label.': '.$measurement;
            })
            ->filter()
            ->implode("\n");
    }
}
