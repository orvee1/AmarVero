<?php

namespace App\Support\Storefront;

use App\Enums\ReviewStatus;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCatalog
{
    private const string EFFECTIVE_PRICE_SQL = 'case when sale_price is not null and regular_price is not null and sale_price < regular_price and (sale_starts_at is null or sale_starts_at <= CURRENT_TIMESTAMP) and (sale_ends_at is null or sale_ends_at >= CURRENT_TIMESTAMP) then sale_price else regular_price end';

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function listingQuery(array $filters = []): Builder
    {
        return Product::query()
            ->published()
            ->with([
                'brand',
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'attributeValues.productAttribute',
                'variants.attributeValues.productAttribute',
            ])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('status', ReviewStatus::Approved)], 'rating')
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('status', ReviewStatus::Approved)])
            ->withSum('variants as stock_on_hand', 'stock_quantity')
            ->withSum('variants as stock_reserved', 'reserved_quantity')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('short_description', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('base_sku', 'like', '%'.$search.'%')
                        ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('categories', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(filled($filters['brand'] ?? null), fn (Builder $query) => $query->where('brand_id', (int) $filters['brand']))
            ->when(filled($filters['category'] ?? null), fn (Builder $query) => $query->whereHas('categories', fn (Builder $query) => $query->whereKey((int) $filters['category'])))
            ->when(filled($filters['collection'] ?? null), fn (Builder $query) => $query->whereHas('collections', fn (Builder $query) => $query->whereKey((int) $filters['collection'])))
            ->when(filled($filters['gender'] ?? null), fn (Builder $query) => $query->where('gender', (string) $filters['gender']))
            ->when(filled($filters['material'] ?? null), fn (Builder $query) => $query->where('material', 'like', '%'.$filters['material'].'%'))
            ->when(filled($filters['minPrice'] ?? null), fn (Builder $query) => $query->whereRaw(self::EFFECTIVE_PRICE_SQL.' >= ?', [(float) $filters['minPrice']]))
            ->when(filled($filters['maxPrice'] ?? null), fn (Builder $query) => $query->whereRaw(self::EFFECTIVE_PRICE_SQL.' <= ?', [(float) $filters['maxPrice']]))
            ->when(($filters['sale'] ?? false) === true, fn (Builder $query) => $this->whereActiveSale($query))
            ->when(($filters['featured'] ?? false) === true, fn (Builder $query) => $query->where('is_featured', true))
            ->when(($filters['new'] ?? false) === true, fn (Builder $query) => $query->where('is_new_arrival', true))
            ->when(($filters['best'] ?? false) === true, fn (Builder $query) => $query->where('is_best_seller', true))
            ->when(($filters['availability'] ?? '') === 'in_stock', fn (Builder $query) => $query->whereHas('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereColumn('stock_quantity', '>', 'reserved_quantity')))
            ->when(($filters['availability'] ?? '') === 'out_of_stock', fn (Builder $query) => $query->whereDoesntHave('variants', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereColumn('stock_quantity', '>', 'reserved_quantity')))
            ->when(($filters['attributeValueIds'] ?? []) !== [], fn (Builder $query) => $query->whereHas('attributeValues', fn (Builder $query) => $query->whereIn('attribute_values.id', $filters['attributeValueIds'])));
    }

    /**
     * @return Builder<Product>
     */
    public function detailQuery(): Builder
    {
        return Product::query()
            ->published()
            ->with([
                'brand',
                'categories',
                'collections',
                'attributeValues.productAttribute',
                'sizeGuides',
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'variants.attributeValues.productAttribute',
                'variants.images',
                'reviews' => fn ($query) => $query->where('status', ReviewStatus::Approved)->with('user')->latest(),
            ])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('status', ReviewStatus::Approved)], 'rating')
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('status', ReviewStatus::Approved)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'parent_id']),
            'collections' => ProductCollection::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'genders' => Product::query()->published()->whereNotNull('gender')->distinct()->orderBy('gender')->pluck('gender')->filter()->values()->all(),
            'materials' => Product::query()->published()->whereNotNull('material')->distinct()->orderBy('material')->pluck('material')->filter()->values()->all(),
            'attributeGroups' => AttributeValue::query()
                ->with('productAttribute')
                ->where('is_active', true)
                ->whereHas('productAttribute', fn (Builder $query) => $query->where('is_filterable', true)->orWhere('is_variant_option', true))
                ->orderBy('sort_order')
                ->orderBy('value')
                ->get()
                ->groupBy('product_attribute_id'),
        ];
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' asc')->orderBy('name'),
            'price_desc' => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' desc')->orderBy('name'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'rating' => $query->orderByDesc('approved_reviews_avg_rating')->latest(),
            'discount' => $query->orderByRaw('(regular_price - '.self::EFFECTIVE_PRICE_SQL.') desc')->latest(),
            'best_selling' => $query->orderByDesc('is_best_seller')->latest(),
            'featured' => $query->orderByDesc('is_featured')->latest(),
            default => $query->latest(),
        };
    }

    public function effectivePrice(Product $product): ?float
    {
        if ($this->isOnSale($product)) {
            return (float) $product->sale_price;
        }

        return $product->regular_price === null ? null : (float) $product->regular_price;
    }

    public function isOnSale(Product $product): bool
    {
        if ($product->sale_price === null || $product->regular_price === null) {
            return false;
        }

        if ((float) $product->sale_price >= (float) $product->regular_price) {
            return false;
        }

        if ($product->sale_starts_at !== null && now()->lessThan($product->sale_starts_at)) {
            return false;
        }

        if ($product->sale_ends_at !== null && now()->greaterThan($product->sale_ends_at)) {
            return false;
        }

        return true;
    }

    public function effectivePriceExpression(): string
    {
        return self::EFFECTIVE_PRICE_SQL;
    }

    public function discountPercent(Product $product): ?int
    {
        if (! $this->isOnSale($product) || $product->regular_price === null || (float) $product->regular_price <= 0) {
            return null;
        }

        return (int) round((1 - ((float) $product->sale_price / (float) $product->regular_price)) * 100);
    }

    public function primaryImage(Product $product): ?ProductImage
    {
        return $product->images->firstWhere('is_primary', true) ?: $product->images->first();
    }

    public function secondaryImage(Product $product): ?ProductImage
    {
        return $product->images->skip(1)->first();
    }

    public function mediaUrl(?string $path, string $disk = 'public'): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk($disk)->url($path);
    }

    public function availableQuantity(Product $product): int
    {
        return $product->variants
            ->where('is_active', true)
            ->sum(fn (ProductVariant $variant): int => $variant->availableQuantity());
    }

    public function stockTone(Product $product): string
    {
        $available = $this->availableQuantity($product);

        if ($available <= 0) {
            return 'rose';
        }

        $lowThreshold = $product->variants
            ->where('is_active', true)
            ->pluck('low_stock_threshold')
            ->filter(fn (mixed $threshold): bool => $threshold !== null)
            ->min();

        return $lowThreshold !== null && $available <= (int) $lowThreshold ? 'amber' : 'teal';
    }

    public function stockLabel(Product $product): string
    {
        $available = $this->availableQuantity($product);

        if ($available <= 0) {
            return __('Out of stock');
        }

        return $this->stockTone($product) === 'amber' ? __('Low stock') : __('In stock');
    }

    /**
     * @return Collection<int, Product>
     */
    public function relatedProducts(Product $product): Collection
    {
        $categoryIds = $product->categories->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

        return Product::query()
            ->published()
            ->with(['brand', 'images', 'variants', 'attributeValues.productAttribute'])
            ->whereKeyNot($product->id)
            ->where(function (Builder $query) use ($product, $categoryIds): void {
                if ($product->brand_id !== null) {
                    $query->where('brand_id', $product->brand_id);
                }

                if ($categoryIds !== [] && $product->brand_id === null) {
                    $query->whereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $categoryIds));
                }

                if ($categoryIds !== [] && $product->brand_id !== null) {
                    $query->orWhereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $categoryIds));
                }
            })
            ->limit(4)
            ->get();
    }

    /**
     * @return array<int, array{id: int, sku: string, label: string, price: float|null, available: int, attribute_value_ids: list<int>, image_url: string|null}>
     */
    public function variantPayload(Product $product): array
    {
        return $product->variants
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(function (ProductVariant $variant) use ($product): array {
                $variantImage = $variant->images->first() ?: $product->images->firstWhere('product_variant_id', $variant->id);

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'label' => $variant->option_label ?: $variant->sku,
                    'price' => $variant->price_override === null ? $this->effectivePrice($product) : (float) $variant->price_override,
                    'available' => $variant->availableQuantity(),
                    'attribute_value_ids' => array_values($variant->attributeValues->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all()),
                    'image_url' => $variantImage instanceof ProductImage ? $this->mediaUrl($variantImage->path, $variantImage->disk) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Product>  $query
     */
    protected function whereActiveSale(Builder $query): void
    {
        $query
            ->whereNotNull('sale_price')
            ->whereNotNull('regular_price')
            ->whereColumn('sale_price', '<', 'regular_price')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('sale_starts_at')
                    ->orWhere('sale_starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('sale_ends_at')
                    ->orWhere('sale_ends_at', '>=', now());
            });
    }

    /**
     * @return Collection<int, AttributeValue>
     */
    public function colorValues(Product $product): Collection
    {
        return $product->attributeValues
            ->filter(fn (AttributeValue $value): bool => in_array(strtolower($value->productAttribute->name), ['color', 'colour'], true))
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function ratingDistribution(Product $product): array
    {
        return DB::table('product_reviews')
            ->select('rating', DB::raw('count(*) as aggregate'))
            ->where('product_id', $product->id)
            ->where('status', ReviewStatus::Approved->value)
            ->groupBy('rating')
            ->pluck('aggregate', 'rating')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }
}
