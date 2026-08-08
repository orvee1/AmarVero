<?php

namespace App\Livewire\Storefront;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Storefront\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProductShow extends Component
{
    public int $productId;

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    /**
     * @var array<int|string, int|string>
     */
    public array $selectedOptions = [];

    public function mount(Product $product): void
    {
        $catalog = app(ProductCatalog::class);
        $publishedProduct = $catalog->detailQuery()
            ->whereKey($product->id)
            ->firstOrFail();

        $this->productId = $publishedProduct->id;
        $firstVariant = $publishedProduct->variants->where('is_active', true)->sortBy('sort_order')->first();

        if ($firstVariant instanceof ProductVariant) {
            $this->selectedVariantId = $firstVariant->id;
            $this->selectedOptions = $firstVariant->attributeValues
                ->mapWithKeys(fn (AttributeValue $value): array => [$value->product_attribute_id => $value->id])
                ->all();
        }
    }

    public function updatedSelectedOptions(): void
    {
        $product = $this->product();
        $selectedIds = $this->selectedOptionIds();

        $matchedVariant = $product->variants
            ->where('is_active', true)
            ->first(function (ProductVariant $variant) use ($selectedIds): bool {
                $variantValueIds = $variant->attributeValues->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

                return $selectedIds !== [] && array_diff($selectedIds, $variantValueIds) === [];
            });

        $this->selectedVariantId = $matchedVariant?->id;
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min(20, $this->quantity + 1);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function render(ProductCatalog $catalog): View
    {
        $product = $this->product();
        $selectedVariant = $this->selectedVariant($product);
        $activeImage = $this->activeImage($product, $selectedVariant, $catalog);
        $ratingDistribution = $catalog->ratingDistribution($product);
        $reviewCount = (int) ($product->approved_reviews_count ?? 0);

        return view('livewire.storefront.product-show', [
            'product' => $product,
            'catalog' => $catalog,
            'selectedVariant' => $selectedVariant,
            'variantPayload' => $catalog->variantPayload($product),
            'optionGroups' => $this->optionGroups($product),
            'activeImageUrl' => $activeImage,
            'relatedProducts' => $catalog->relatedProducts($product),
            'reviewRows' => $this->reviewRows($ratingDistribution, $reviewCount),
        ])->layout('components.layouts.storefront', [
            'title' => $product->seo_title ?: $product->name,
        ]);
    }

    protected function product(): Product
    {
        return app(ProductCatalog::class)
            ->detailQuery()
            ->whereKey($this->productId)
            ->firstOrFail();
    }

    protected function selectedVariant(Product $product): ?ProductVariant
    {
        if ($this->selectedVariantId === null) {
            return null;
        }

        $variant = $product->variants->firstWhere('id', $this->selectedVariantId);

        return $variant instanceof ProductVariant ? $variant : null;
    }

    protected function activeImage(Product $product, ?ProductVariant $selectedVariant, ProductCatalog $catalog): ?string
    {
        $variantImage = $selectedVariant?->images->first();

        if ($variantImage instanceof ProductImage) {
            return $catalog->mediaUrl($variantImage->path, $variantImage->disk);
        }

        $primaryImage = $catalog->primaryImage($product);

        return $primaryImage instanceof ProductImage ? $catalog->mediaUrl($primaryImage->path, $primaryImage->disk) : null;
    }

    /**
     * @return Collection<int, Collection<int, AttributeValue>>
     */
    protected function optionGroups(Product $product): Collection
    {
        return $product->variants
            ->toBase()
            ->where('is_active', true)
            ->flatMap(fn (ProductVariant $variant): Collection => $variant->attributeValues->toBase())
            ->filter(fn (AttributeValue $value): bool => (bool) $value->productAttribute->is_variant_option)
            ->unique('id')
            ->sortBy(fn (AttributeValue $value): string => sprintf(
                '%010d-%010d-%010d-%s',
                $value->productAttribute->sort_order,
                $value->product_attribute_id,
                $value->sort_order,
                $value->value,
            ))
            ->groupBy('product_attribute_id')
            ->map(fn (Collection $values): Collection => $values->values())
            ->values();
    }

    /**
     * @return list<int>
     */
    protected function selectedOptionIds(): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($this->selectedOptions, static fn (mixed $id): bool => filled($id)),
        )));
    }

    /**
     * @param  array<int|string, int>  $ratingDistribution
     * @return list<array{rating: int, count: int, percent: int}>
     */
    protected function reviewRows(array $ratingDistribution, int $reviewCount): array
    {
        $rows = [];

        foreach ([5, 4, 3, 2, 1] as $rating) {
            $count = $ratingDistribution[(string) $rating] ?? $ratingDistribution[$rating] ?? 0;

            $rows[] = [
                'rating' => $rating,
                'count' => $count,
                'percent' => $reviewCount > 0 ? (int) round(($count / $reviewCount) * 100) : 0,
            ];
        }

        return $rows;
    }
}
