<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\InventoryMovementType;
use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\Inventory\AdjustVariantInventory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryIndex extends Component
{
    use InteractsWithCatalogForms, WithPagination;

    public string $search = '';

    public string $productFilter = '';

    public string $stockFilter = 'all';

    public bool $showAdjustmentForm = false;

    /**
     * @var list<int|string>
     */
    public array $selectedVariantIds = [];

    public string $bulkMovementType = 'adjustment';

    public string $bulkAdjustmentQuantity = '';

    public string $bulkAdjustmentReason = '';

    /**
     * @var array<string, mixed>
     */
    public array $adjustmentForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', ProductVariant::class);

        $this->resetAdjustmentForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function adjust(int $variantId): void
    {
        $this->authorizeInventoryUpdate();

        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail($variantId);

        $this->adjustmentForm = [
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $variant->product->name,
            'type' => InventoryMovementType::Adjustment->value,
            'quantity' => '',
            'reason' => '',
            'notes' => '',
        ];
        $this->showAdjustmentForm = true;
    }

    public function saveAdjustment(): void
    {
        $user = $this->authorizeInventoryUpdate();

        $validated = $this->validate($this->adjustmentRules())['adjustmentForm'];
        $variant = ProductVariant::query()->findOrFail((int) $validated['product_variant_id']);

        app(AdjustVariantInventory::class)->execute(
            variant: $variant,
            type: InventoryMovementType::from($validated['type']),
            quantity: (int) $validated['quantity'],
            user: $user,
            reason: $this->nullableString($validated['reason'] ?? null),
            notes: $this->nullableString($validated['notes'] ?? null),
        );

        $this->resetAdjustmentForm();
        $this->showAdjustmentForm = false;

        Flux::toast(variant: 'success', text: __('Inventory adjusted.'));
    }

    public function bulkAdjustSelected(): void
    {
        $user = $this->authorizeInventoryUpdate();

        $this->validate([
            'selectedVariantIds' => ['required', 'array', 'min:1'],
            'selectedVariantIds.*' => ['integer', 'exists:product_variants,id'],
            'bulkMovementType' => ['required', Rule::in($this->movementTypeValues())],
            'bulkAdjustmentQuantity' => ['required', 'integer', 'not_in:0'],
            'bulkAdjustmentReason' => ['nullable', 'string', 'max:255'],
        ]);

        $variantIds = $this->integerIds($this->selectedVariantIds);
        $adjuster = app(AdjustVariantInventory::class);

        ProductVariant::query()
            ->whereKey($variantIds)
            ->get()
            ->each(function (ProductVariant $variant) use ($adjuster, $user): void {
                $adjuster->execute(
                    variant: $variant,
                    type: InventoryMovementType::from($this->bulkMovementType),
                    quantity: (int) $this->bulkAdjustmentQuantity,
                    user: $user,
                    reason: $this->nullableString($this->bulkAdjustmentReason),
                    notes: __('Bulk inventory adjustment from admin inventory screen.'),
                );
            });

        $this->selectedVariantIds = [];
        $this->bulkAdjustmentQuantity = '';
        $this->bulkAdjustmentReason = '';

        Flux::toast(variant: 'success', text: __('Selected inventory updated.'));
    }

    public function cancelAdjustment(): void
    {
        $this->resetAdjustmentForm();
        $this->showAdjustmentForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProductVariant::class);

        $variants = ProductVariant::query()
            ->with(['product.brand', 'attributeValues.productAttribute'])
            ->withCount('inventoryMovements')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('sku', 'like', '%'.$this->search.'%')
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

        return view('livewire.admin.catalog.inventory-index', [
            'variants' => $variants,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'movementTypes' => InventoryMovementType::cases(),
            'recentMovements' => InventoryMovement::query()
                ->with(['productVariant.product', 'user'])
                ->latest()
                ->limit(12)
                ->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Inventory'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Inventory') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function adjustmentRules(): array
    {
        return [
            'adjustmentForm.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'adjustmentForm.type' => ['required', Rule::in($this->movementTypeValues())],
            'adjustmentForm.quantity' => ['required', 'integer', 'not_in:0'],
            'adjustmentForm.reason' => ['nullable', 'string', 'max:255'],
            'adjustmentForm.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function resetAdjustmentForm(): void
    {
        $this->adjustmentForm = [
            'product_variant_id' => '',
            'sku' => '',
            'product_name' => '',
            'type' => InventoryMovementType::Adjustment->value,
            'quantity' => '',
            'reason' => '',
            'notes' => '',
        ];

        $this->resetValidation();
    }

    protected function authorizeInventoryUpdate(): User
    {
        Gate::authorize('create', InventoryMovement::class);

        $user = auth()->user();

        abort_unless(
            $user instanceof User && $user->can(AdminPermissions::permission('inventory', 'update')),
            403,
        );

        return $user;
    }

    /**
     * @return list<string>
     */
    protected function movementTypeValues(): array
    {
        return array_map(fn (InventoryMovementType $type): string => $type->value, InventoryMovementType::cases());
    }
}
