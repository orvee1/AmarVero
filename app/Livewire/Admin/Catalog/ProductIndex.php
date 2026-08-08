<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCollection;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use InteractsWithCatalogForms, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $brandFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    /**
     * @var list<int|string>
     */
    public array $selectedProductIds = [];

    public string $bulkStatus = 'draft';

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Product::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $productId): void
    {
        $product = Product::query()
            ->with(['categories', 'collections', 'attributeValues'])
            ->findOrFail($productId);

        Gate::authorize('update', $product);

        $this->editingId = $product->id;
        $this->form = [
            'brand_id' => $product->brand_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'status' => $this->productStatusValue($product),
            'gender' => $product->gender,
            'material' => $product->material,
            'care_instructions' => $product->care_instructions,
            'regular_price' => $product->regular_price,
            'sale_price' => $product->sale_price,
            'cost_price' => $product->cost_price,
            'sale_starts_at' => $this->dateTimeInput($product->sale_starts_at),
            'sale_ends_at' => $this->dateTimeInput($product->sale_ends_at),
            'published_at' => $this->dateTimeInput($product->published_at),
            'is_featured' => $product->is_featured,
            'is_new_arrival' => $product->is_new_arrival,
            'is_best_seller' => $product->is_best_seller,
            'track_inventory' => $product->track_inventory,
            'allow_backorder' => $product->allow_backorder,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'category_ids' => $product->categories->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            'collection_ids' => $product->collections->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            'attribute_value_ids' => $product->attributeValues->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $product = $this->editingId
            ? Product::query()->findOrFail($this->editingId)
            : new Product;

        Gate::authorize($product->exists ? 'update' : 'create', $product->exists ? $product : Product::class);

        $validated = $this->validate($this->rules())['form'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        if (filled($validated['regular_price'] ?? null) && filled($validated['sale_price'] ?? null) && (float) $validated['sale_price'] > (float) $validated['regular_price']) {
            $this->addError('form.sale_price', __('Sale price cannot be greater than the regular price.'));

            return;
        }

        if (($validated['status'] ?? null) === ProductStatus::Scheduled->value && blank($validated['published_at'] ?? null)) {
            $this->addError('form.published_at', __('Scheduled products require a publish date.'));

            return;
        }

        DB::transaction(function () use ($product, $validated): void {
            $status = ProductStatus::from($validated['status']);
            $publishedAt = $this->nullableDateTime($validated['published_at'] ?? null);

            if ($status === ProductStatus::Published && $publishedAt === null) {
                $publishedAt = now()->format('Y-m-d H:i:s');
            }

            if (in_array($status, [ProductStatus::Draft, ProductStatus::Archived], true)) {
                $publishedAt = null;
            }

            $product->fill([
                'brand_id' => $this->nullableInteger($validated['brand_id'] ?? null),
                'name' => $this->nullableString($validated['name']),
                'slug' => $validated['slug'],
                'base_sku' => $this->nullableString($validated['base_sku'] ?? null),
                'short_description' => $this->nullableString($validated['short_description'] ?? null),
                'description' => $this->nullableString($validated['description'] ?? null),
                'status' => $status,
                'gender' => $this->nullableString($validated['gender'] ?? null),
                'material' => $this->nullableString($validated['material'] ?? null),
                'care_instructions' => $this->nullableString($validated['care_instructions'] ?? null),
                'regular_price' => $this->nullableDecimal($validated['regular_price'] ?? null),
                'sale_price' => $this->nullableDecimal($validated['sale_price'] ?? null),
                'cost_price' => $this->nullableDecimal($validated['cost_price'] ?? null),
                'sale_starts_at' => $this->nullableDateTime($validated['sale_starts_at'] ?? null),
                'sale_ends_at' => $this->nullableDateTime($validated['sale_ends_at'] ?? null),
                'published_at' => $publishedAt,
                'is_featured' => $this->booleanValue($validated['is_featured'] ?? false),
                'is_new_arrival' => $this->booleanValue($validated['is_new_arrival'] ?? false),
                'is_best_seller' => $this->booleanValue($validated['is_best_seller'] ?? false),
                'track_inventory' => $this->booleanValue($validated['track_inventory'] ?? false),
                'allow_backorder' => $this->booleanValue($validated['allow_backorder'] ?? false),
                'seo_title' => $this->nullableString($validated['seo_title'] ?? null),
                'seo_description' => $this->nullableString($validated['seo_description'] ?? null),
            ])->save();

            $product->categories()->sync($this->integerIds($validated['category_ids'] ?? []));
            $product->collections()->sync($this->integerIds($validated['collection_ids'] ?? []));
            $product->attributeValues()->sync($this->integerIds($validated['attribute_value_ids'] ?? []));
        });

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Product saved.'));
    }

    public function updateSelectedStatus(): void
    {
        Gate::authorize('update', new Product);

        $productIds = $this->integerIds($this->selectedProductIds);

        $this->validate([
            'bulkStatus' => ['required', Rule::in($this->bulkStatusValues())],
            'selectedProductIds' => ['required', 'array', 'min:1'],
        ]);

        $status = ProductStatus::from($this->bulkStatus);

        Product::query()
            ->whereKey($productIds)
            ->update([
                'status' => $status,
                'published_at' => $status === ProductStatus::Published ? now() : null,
            ]);

        $this->selectedProductIds = [];

        Flux::toast(variant: 'success', text: __('Selected products updated.'));
    }

    public function delete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        Gate::authorize('delete', $product);

        $product->delete();

        if ($this->editingId === $productId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->with('brand')
            ->withCount(['categories', 'collections', 'variants', 'images'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%')
                        ->orWhere('base_sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->brandFilter !== '', fn ($query) => $query->where('brand_id', $this->brandFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.catalog.product-index', [
            'products' => $products,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'collections' => ProductCollection::query()->orderBy('name')->get(['id', 'name']),
            'attributeGroups' => ProductAttribute::query()
                ->with(['values' => fn ($query) => $query->orderBy('sort_order')->orderBy('value')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'statuses' => ProductStatus::cases(),
            'bulkStatuses' => $this->bulkStatusValues(),
        ])->layout('components.layouts.admin', [
            'title' => __('Products'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Products') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique|In>>
     */
    protected function rules(): array
    {
        return [
            'form.brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->editingId),
            ],
            'form.base_sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'base_sku')->ignore($this->editingId),
            ],
            'form.short_description' => ['nullable', 'string', 'max:500'],
            'form.description' => ['nullable', 'string'],
            'form.status' => ['required', Rule::in(array_map(fn (ProductStatus $status): string => $status->value, ProductStatus::cases()))],
            'form.gender' => ['nullable', 'string', 'max:50'],
            'form.material' => ['nullable', 'string', 'max:255'],
            'form.care_instructions' => ['nullable', 'string'],
            'form.regular_price' => ['nullable', 'numeric', 'min:0'],
            'form.sale_price' => ['nullable', 'numeric', 'min:0'],
            'form.cost_price' => ['nullable', 'numeric', 'min:0'],
            'form.sale_starts_at' => ['nullable', 'date'],
            'form.sale_ends_at' => ['nullable', 'date', 'after_or_equal:form.sale_starts_at'],
            'form.published_at' => ['nullable', 'date'],
            'form.is_featured' => ['boolean'],
            'form.is_new_arrival' => ['boolean'],
            'form.is_best_seller' => ['boolean'],
            'form.track_inventory' => ['boolean'],
            'form.allow_backorder' => ['boolean'],
            'form.seo_title' => ['nullable', 'string', 'max:255'],
            'form.seo_description' => ['nullable', 'string', 'max:500'],
            'form.category_ids' => ['array'],
            'form.category_ids.*' => ['integer', 'exists:categories,id'],
            'form.collection_ids' => ['array'],
            'form.collection_ids.*' => ['integer', 'exists:product_collections,id'],
            'form.attribute_value_ids' => ['array'],
            'form.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }

    /**
     * @return list<string>
     */
    protected function bulkStatusValues(): array
    {
        return [
            ProductStatus::Draft->value,
            ProductStatus::Published->value,
            ProductStatus::Archived->value,
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'brand_id' => '',
            'name' => '',
            'slug' => '',
            'base_sku' => '',
            'short_description' => '',
            'description' => '',
            'status' => ProductStatus::Draft->value,
            'gender' => '',
            'material' => '',
            'care_instructions' => '',
            'regular_price' => '',
            'sale_price' => '',
            'cost_price' => '',
            'sale_starts_at' => '',
            'sale_ends_at' => '',
            'published_at' => '',
            'is_featured' => false,
            'is_new_arrival' => false,
            'is_best_seller' => false,
            'track_inventory' => true,
            'allow_backorder' => false,
            'seo_title' => '',
            'seo_description' => '',
            'category_ids' => [],
            'collection_ids' => [],
            'attribute_value_ids' => [],
        ];

        $this->resetValidation();
    }

    protected function productStatusValue(Product $product): string
    {
        $status = $product->getAttribute('status');

        return $status instanceof ProductStatus ? $status->value : (string) $status;
    }
}
