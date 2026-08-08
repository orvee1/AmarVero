<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ImageIndex extends Component
{
    use InteractsWithCatalogForms, WithFileUploads, WithPagination;

    public string $search = '';

    public string $productFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public mixed $imageUpload = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', ProductImage::class);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFormProductId(): void
    {
        $this->form['product_variant_id'] = '';
    }

    public function create(): void
    {
        Gate::authorize('create', ProductImage::class);

        $this->resetForm();
        $this->form['product_id'] = $this->productFilter;
        $this->showForm = true;
    }

    public function edit(int $imageId): void
    {
        $image = ProductImage::query()->findOrFail($imageId);

        Gate::authorize('update', $image);

        $this->editingId = $image->id;
        $this->imageUpload = null;
        $this->form = [
            'product_id' => $image->product_id,
            'product_variant_id' => $image->product_variant_id,
            'path' => $image->path,
            'alt_text' => $image->alt_text,
            'is_primary' => $image->is_primary,
            'sort_order' => $image->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $image = $this->editingId
            ? ProductImage::query()->findOrFail($this->editingId)
            : new ProductImage;

        Gate::authorize($image->exists ? 'update' : 'create', $image->exists ? $image : ProductImage::class);

        $validated = $this->validate($this->rules());
        $form = $validated['form'];
        $productId = (int) $form['product_id'];
        $variantId = $this->nullableInteger($form['product_variant_id'] ?? null);

        if ($variantId !== null && ! ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->exists()) {
            throw ValidationException::withMessages([
                'form.product_variant_id' => __('The selected variant does not belong to this product.'),
            ]);
        }

        $path = $this->nullableString($form['path'] ?? null);

        if ($this->imageUpload !== null) {
            $path = $this->imageUpload->store('products/'.$productId, 'public');
        }

        DB::transaction(function () use ($image, $form, $productId, $variantId, $path): void {
            $isPrimary = $this->booleanValue($form['is_primary'] ?? false);

            if (! $image->exists && ! ProductImage::query()->where('product_id', $productId)->where('is_primary', true)->exists()) {
                $isPrimary = true;
            }

            if ($isPrimary) {
                ProductImage::query()
                    ->where('product_id', $productId)
                    ->when($image->exists, fn ($query) => $query->whereKeyNot($image->id))
                    ->update(['is_primary' => false]);
            }

            $image->fill([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'disk' => 'public',
                'path' => $path,
                'alt_text' => $this->nullableString($form['alt_text'] ?? null),
                'is_primary' => $isPrimary,
                'sort_order' => $this->integerValue($form['sort_order'] ?? 0),
            ])->save();
        });

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Product image saved.'));
    }

    public function delete(int $imageId): void
    {
        $image = ProductImage::query()->findOrFail($imageId);

        Gate::authorize('delete', $image);

        $productId = $image->product_id;
        $wasPrimary = $image->is_primary;

        if ($image->disk === 'public' && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        if ($wasPrimary) {
            ProductImage::query()
                ->where('product_id', $productId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->forceFill(['is_primary' => true])
                ->save();
        }

        if ($this->editingId === $imageId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Product image deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProductImage::class);

        $images = ProductImage::query()
            ->with(['product.brand', 'productVariant'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('path', 'like', '%'.$this->search.'%')
                        ->orWhere('alt_text', 'like', '%'.$this->search.'%')
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('productVariant', fn ($query) => $query->where('sku', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->productFilter !== '', fn ($query) => $query->where('product_id', $this->productFilter))
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.catalog.image-index', [
            'images' => $images,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'variantsForForm' => $this->variantOptionsForForm(),
        ])->layout('components.layouts.admin', [
            'title' => __('Product images'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Images') => null,
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
            'form.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'form.path' => ['nullable', 'string', 'max:2048', 'required_without:imageUpload'],
            'form.alt_text' => ['nullable', 'string', 'max:255'],
            'form.is_primary' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'imageUpload' => ['nullable', File::image()->max(4096)],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->imageUpload = null;
        $this->form = [
            'product_id' => '',
            'product_variant_id' => '',
            'path' => '',
            'alt_text' => '',
            'is_primary' => false,
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    /**
     * @return EloquentCollection<int, ProductVariant>
     */
    protected function variantOptionsForForm(): EloquentCollection
    {
        if (! filled($this->form['product_id'] ?? null)) {
            return new EloquentCollection;
        }

        return ProductVariant::query()
            ->where('product_id', (int) $this->form['product_id'])
            ->orderBy('sort_order')
            ->orderBy('sku')
            ->get(['id', 'product_id', 'sku', 'option_label']);
    }
}
