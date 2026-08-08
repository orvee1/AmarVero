<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VariantIndex extends Component
{
    use InteractsWithCatalogForms, WithPagination;

    public string $search = '';

    #[Url(as: 'product')]
    public string $productFilter = '';

    public string $stockFilter = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $generationProductId = '';

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', ProductVariant::class);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProductFilter(): void
    {
        $this->generationProductId = $this->productFilter;
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', ProductVariant::class);

        $this->resetForm();
        $this->form['product_id'] = $this->productFilter;
        $this->showForm = true;
    }

    public function edit(int $variantId): void
    {
        $variant = ProductVariant::query()
            ->with('attributeValues')
            ->findOrFail($variantId);

        Gate::authorize('update', $variant);

        $this->editingId = $variant->id;
        $variantDimensions = $variant->getAttribute('dimensions');
        $dimensions = is_array($variantDimensions) ? $variantDimensions : [];
        $this->form = [
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'option_label' => $variant->option_label,
            'price_override' => $variant->price_override,
            'compare_at_price' => $variant->compare_at_price,
            'cost_price' => $variant->cost_price,
            'stock_quantity' => $variant->stock_quantity,
            'reserved_quantity' => $variant->reserved_quantity,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'allow_backorder' => $variant->allow_backorder,
            'is_active' => $variant->is_active,
            'sort_order' => $variant->sort_order,
            'weight_grams' => $variant->weight_grams,
            'length_cm' => $dimensions['length_cm'] ?? '',
            'width_cm' => $dimensions['width_cm'] ?? '',
            'height_cm' => $dimensions['height_cm'] ?? '',
            'attribute_value_ids' => $variant->attributeValues->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $variant = $this->editingId
            ? ProductVariant::query()->findOrFail($this->editingId)
            : new ProductVariant;

        Gate::authorize($variant->exists ? 'update' : 'create', $variant->exists ? $variant : ProductVariant::class);

        if (filled($this->form['product_id'] ?? null) && blank($this->form['sku'] ?? null)) {
            $productForSku = Product::query()->find((int) $this->form['product_id']);

            if ($productForSku instanceof Product) {
                $this->form['sku'] = $this->uniqueSku($this->baseSku($productForSku, $this->integerIds($this->form['attribute_value_ids'] ?? [])), $this->editingId);
            }
        }

        $validated = $this->validate($this->rules())['form'];
        $product = Product::query()
            ->with(['attributeValues.productAttribute'])
            ->findOrFail((int) $validated['product_id']);
        $attributeValueIds = $this->integerIds($validated['attribute_value_ids'] ?? []);

        $this->ensureVariantValuesAreValid($product, $attributeValueIds);

        if (blank($validated['option_label'] ?? null)) {
            $validated['option_label'] = $this->optionLabel($attributeValueIds);
        }

        DB::transaction(function () use ($variant, $validated, $attributeValueIds): void {
            $variant->fill([
                'product_id' => (int) $validated['product_id'],
                'sku' => Str::upper((string) $validated['sku']),
                'barcode' => $this->nullableString($validated['barcode'] ?? null),
                'option_label' => $this->nullableString($validated['option_label'] ?? null),
                'price_override' => $this->nullableDecimal($validated['price_override'] ?? null),
                'compare_at_price' => $this->nullableDecimal($validated['compare_at_price'] ?? null),
                'cost_price' => $this->nullableDecimal($validated['cost_price'] ?? null),
                'stock_quantity' => $this->integerValue($validated['stock_quantity'] ?? 0),
                'reserved_quantity' => $this->integerValue($validated['reserved_quantity'] ?? 0),
                'low_stock_threshold' => $this->nullableInteger($validated['low_stock_threshold'] ?? null),
                'allow_backorder' => $this->booleanValue($validated['allow_backorder'] ?? false),
                'is_active' => $this->booleanValue($validated['is_active'] ?? false),
                'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
                'weight_grams' => $this->nullableDecimal($validated['weight_grams'] ?? null),
                'dimensions' => $this->dimensions($validated),
            ])->save();

            $variant->attributeValues()->sync($attributeValueIds);
        });

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Variant saved.'));
    }

    public function generateVariants(): void
    {
        Gate::authorize('create', ProductVariant::class);

        $this->validate([
            'generationProductId' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::query()
            ->with(['attributeValues.productAttribute', 'variants.attributeValues'])
            ->findOrFail((int) $this->generationProductId);

        $groups = $this->variantOptionGroups($product);

        if ($groups === []) {
            throw ValidationException::withMessages([
                'generationProductId' => __('Assign active variant-option attribute values to the product before generating variants.'),
            ]);
        }

        $combinations = $this->combinations($groups);

        if (count($combinations) > 80) {
            throw ValidationException::withMessages([
                'generationProductId' => __('Variant generation is limited to 80 combinations at a time.'),
            ]);
        }

        $existingCombinations = $product->variants
            ->map(fn (ProductVariant $variant): string => $this->combinationKey($this->attributeValueIdsForVariant($variant)))
            ->all();

        $created = 0;

        DB::transaction(function () use ($product, $combinations, $existingCombinations, &$created): void {
            foreach ($combinations as $combination) {
                if (in_array($this->combinationKey($combination), $existingCombinations, true)) {
                    continue;
                }

                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => $this->uniqueSku($this->baseSku($product, $combination)),
                    'option_label' => $this->optionLabel($combination),
                    'stock_quantity' => 0,
                    'reserved_quantity' => 0,
                    'allow_backorder' => $product->allow_backorder,
                    'is_active' => true,
                    'sort_order' => ProductVariant::query()->where('product_id', $product->id)->max('sort_order') + 1,
                ]);

                $variant->attributeValues()->sync($combination);
                $created++;
            }
        });

        Flux::toast(variant: 'success', text: trans_choice(':count variant generated.|:count variants generated.', $created, ['count' => $created]));
    }

    public function delete(int $variantId): void
    {
        $variant = ProductVariant::query()->findOrFail($variantId);

        Gate::authorize('delete', $variant);

        $variant->delete();

        if ($this->editingId === $variantId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Variant deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProductVariant::class);

        $variants = ProductVariant::query()
            ->with(['product.brand', 'attributeValues.productAttribute'])
            ->withCount(['images', 'inventoryMovements'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('barcode', 'like', '%'.$this->search.'%')
                        ->orWhere('option_label', 'like', '%'.$this->search.'%')
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->productFilter !== '', fn ($query) => $query->where('product_id', $this->productFilter))
            ->when($this->stockFilter === 'out', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'reserved_quantity'))
            ->when($this->stockFilter === 'low', fn ($query) => $query
                ->whereNotNull('low_stock_threshold')
                ->whereColumn('stock_quantity', '>', 'reserved_quantity')
                ->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold'))
            ->when($this->stockFilter === 'available', fn ($query) => $query->whereColumn('stock_quantity', '>', 'reserved_quantity'))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.catalog.variant-index', [
            'variants' => $variants,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'base_sku']),
            'attributeGroups' => ProductAttribute::query()
                ->with(['values' => fn ($query) => $query->orderBy('sort_order')->orderBy('value')])
                ->where('is_variant_option', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Product variants'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Variants') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'form.product_id' => ['required', 'integer', 'exists:products,id'],
            'form.sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($this->editingId),
            ],
            'form.barcode' => ['nullable', 'string', 'max:255'],
            'form.option_label' => ['nullable', 'string', 'max:255'],
            'form.price_override' => ['nullable', 'numeric', 'min:0'],
            'form.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'form.cost_price' => ['nullable', 'numeric', 'min:0'],
            'form.stock_quantity' => ['required', 'integer', 'min:0'],
            'form.reserved_quantity' => ['required', 'integer', 'min:0', 'lte:form.stock_quantity'],
            'form.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'form.allow_backorder' => ['boolean'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.weight_grams' => ['nullable', 'numeric', 'min:0'],
            'form.length_cm' => ['nullable', 'numeric', 'min:0'],
            'form.width_cm' => ['nullable', 'numeric', 'min:0'],
            'form.height_cm' => ['nullable', 'numeric', 'min:0'],
            'form.attribute_value_ids' => ['array'],
            'form.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'product_id' => '',
            'sku' => '',
            'barcode' => '',
            'option_label' => '',
            'price_override' => '',
            'compare_at_price' => '',
            'cost_price' => '',
            'stock_quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => '',
            'allow_backorder' => false,
            'is_active' => true,
            'sort_order' => 0,
            'weight_grams' => '',
            'length_cm' => '',
            'width_cm' => '',
            'height_cm' => '',
            'attribute_value_ids' => [],
        ];

        $this->resetValidation();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>|null
     */
    protected function dimensions(array $validated): ?array
    {
        $dimensions = array_filter([
            'length_cm' => $this->nullableDecimal($validated['length_cm'] ?? null),
            'width_cm' => $this->nullableDecimal($validated['width_cm'] ?? null),
            'height_cm' => $this->nullableDecimal($validated['height_cm'] ?? null),
        ]);

        return $dimensions === [] ? null : $dimensions;
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    protected function ensureVariantValuesAreValid(Product $product, array $attributeValueIds): void
    {
        $productAttributeValueIds = $product->attributeValues->pluck('id')->map(fn (int $id): int => $id)->all();

        if (array_diff($attributeValueIds, $productAttributeValueIds) !== []) {
            throw ValidationException::withMessages([
                'form.attribute_value_ids' => __('Variant options must be assigned to the selected product first.'),
            ]);
        }

        $values = AttributeValue::query()
            ->with('productAttribute')
            ->whereKey($attributeValueIds)
            ->get();

        if ($values->pluck('product_attribute_id')->unique()->count() !== $values->count()) {
            throw ValidationException::withMessages([
                'form.attribute_value_ids' => __('Select only one value from each variant option group.'),
            ]);
        }

        $variantOptionCount = $values
            ->filter(fn (AttributeValue $value): bool => (bool) $value->productAttribute?->is_variant_option)
            ->count();

        if ($variantOptionCount !== $values->count()) {
            throw ValidationException::withMessages([
                'form.attribute_value_ids' => __('Variants can only use attributes marked as variant options.'),
            ]);
        }

        $duplicateExists = ProductVariant::query()
            ->where('product_id', $product->id)
            ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))
            ->with('attributeValues:id')
            ->get()
            ->contains(fn (ProductVariant $variant): bool => $this->combinationKey($this->attributeValueIdsForVariant($variant)) === $this->combinationKey($attributeValueIds));

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'form.attribute_value_ids' => __('A variant with this option combination already exists.'),
            ]);
        }
    }

    /**
     * @return list<list<int>>
     */
    protected function variantOptionGroups(Product $product): array
    {
        $groups = $product->attributeValues
            ->filter(fn (AttributeValue $value): bool => (bool) $value->is_active && (bool) $value->productAttribute?->is_variant_option)
            ->sortBy(fn (AttributeValue $value): string => sprintf(
                '%010d-%010d-%010d-%s',
                $value->productAttribute->sort_order,
                $value->product_attribute_id,
                $value->sort_order,
                $value->value,
            ))
            ->groupBy('product_attribute_id')
            ->map(fn (Collection $values): array => array_values($values->pluck('id')->map(fn (int $id): int => $id)->all()))
            ->values()
            ->all();

        return array_values($groups);
    }

    /**
     * @param  list<list<int>>  $groups
     * @return list<list<int>>
     */
    protected function combinations(array $groups): array
    {
        $combinations = [[]];

        foreach ($groups as $group) {
            $nextCombinations = [];

            foreach ($combinations as $combination) {
                foreach ($group as $attributeValueId) {
                    $nextCombinations[] = array_merge($combination, [$attributeValueId]);
                }
            }

            $combinations = $nextCombinations;
        }

        return $combinations;
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    protected function optionLabel(array $attributeValueIds): ?string
    {
        if ($attributeValueIds === []) {
            return null;
        }

        return $this->orderedAttributeValues($attributeValueIds)
            ->map(fn (AttributeValue $value): string => $value->display_value ?: $value->value)
            ->implode(' / ');
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    protected function baseSku(Product $product, array $attributeValueIds): string
    {
        $base = Str::upper(Str::slug($product->base_sku ?: $product->slug ?: 'AMV-'.$product->id, '-'));
        $suffix = $this->orderedAttributeValues($attributeValueIds)
            ->pluck('slug')
            ->map(fn (string $slug): string => Str::upper(Str::slug($slug, '-')))
            ->implode('-');

        return $suffix === '' ? $base : $base.'-'.$suffix;
    }

    /**
     * @param  list<int>  $attributeValueIds
     * @return Collection<int, AttributeValue>
     */
    protected function orderedAttributeValues(array $attributeValueIds): Collection
    {
        return AttributeValue::query()
            ->with('productAttribute')
            ->whereKey($attributeValueIds)
            ->get()
            ->sortBy(fn (AttributeValue $value): string => sprintf(
                '%010d-%010d-%010d-%s',
                $value->productAttribute->sort_order,
                $value->product_attribute_id,
                $value->sort_order,
                $value->value,
            ))
            ->values();
    }

    protected function uniqueSku(string $baseSku, ?int $ignoreVariantId = null): string
    {
        $candidate = $baseSku;
        $suffix = 2;

        while (ProductVariant::query()
            ->where('sku', $candidate)
            ->when($ignoreVariantId, fn ($query) => $query->whereKeyNot($ignoreVariantId))
            ->exists()) {
            $candidate = $baseSku.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return list<int>
     */
    protected function attributeValueIdsForVariant(ProductVariant $variant): array
    {
        return array_values($variant->attributeValues->pluck('id')->map(fn (int $id): int => $id)->all());
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    protected function combinationKey(array $attributeValueIds): string
    {
        sort($attributeValueIds);

        return implode('-', $attributeValueIds);
    }
}
