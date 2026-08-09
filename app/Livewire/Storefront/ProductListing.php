<?php

namespace App\Livewire\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductCollection;
use App\Support\Seo\SeoManager;
use App\Support\Storefront\ProductCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductListing extends Component
{
    use WithPagination;

    public string $context = 'shop';

    public ?string $slug = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    #[Url(except: '')]
    public string $brand = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $collection = '';

    #[Url(except: '')]
    public string $gender = '';

    #[Url(except: '')]
    public string $material = '';

    #[Url(except: '')]
    public string $minPrice = '';

    #[Url(except: '')]
    public string $maxPrice = '';

    #[Url(except: '')]
    public string $availability = '';

    #[Url(except: false)]
    public bool $sale = false;

    #[Url(except: false)]
    public bool $featured = false;

    #[Url(as: 'new', except: false)]
    public bool $newArrival = false;

    #[Url(except: false)]
    public bool $best = false;

    #[Url(except: 'grid')]
    public string $viewMode = 'grid';

    #[Url(except: 12)]
    public int $perPage = 12;

    /**
     * @var list<int|string>
     */
    #[Url(as: 'attrs', except: [])]
    public array $attributeValueIds = [];

    public function mount(?string $context = null, ?string $slug = null): void
    {
        $this->context = $context === null || $context === 'shop' ? $this->contextFromRouteName() : $context;
        $this->slug = $slug ?? $this->slugFromRoute();

        $this->applyContextDefaults();
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->brand = '';
        $this->category = '';
        $this->collection = '';
        $this->gender = '';
        $this->material = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->availability = '';
        $this->sale = false;
        $this->featured = false;
        $this->newArrival = false;
        $this->best = false;
        $this->attributeValueIds = [];

        $this->applyContextDefaults();
        $this->resetPage();
    }

    public function removeFilter(string $filter): void
    {
        match ($filter) {
            'search' => $this->search = '',
            'brand' => $this->brand = '',
            'category' => $this->category = '',
            'collection' => $this->collection = '',
            'gender' => $this->gender = '',
            'material' => $this->material = '',
            'price' => [$this->minPrice, $this->maxPrice] = ['', ''],
            'availability' => $this->availability = '',
            'sale' => $this->sale = false,
            'featured' => $this->featured = false,
            'new' => $this->newArrival = false,
            'best' => $this->best = false,
            'attributes' => $this->attributeValueIds = [],
            default => null,
        };

        $this->applyContextDefaults();
        $this->resetPage();
    }

    public function render(ProductCatalog $catalog): View
    {
        $filters = $this->filters();
        $query = $catalog->listingQuery($filters);
        $products = $catalog
            ->applySort($query, $this->sort)
            ->paginate($this->safePerPage());

        return view('livewire.storefront.product-listing', array_merge($catalog->filterOptions(), [
            'products' => $products,
            'catalog' => $catalog,
            'pageTitle' => $this->pageTitle(),
            'pageDescription' => $this->pageDescription(),
            'activeFilters' => $this->activeFilters(),
            'sortOptions' => $this->sortOptions(),
            'perPageOptions' => [12, 24, 36],
        ]))->layout('components.layouts.storefront', [
            'title' => $this->pageTitle(),
            'seo' => app(SeoManager::class)->listing(
                title: $this->pageTitle(),
                description: $this->pageDescription(),
                canonical: $this->canonicalUrl(),
                noindex: $this->shouldNoindexListing(),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'brand' => $this->brand,
            'category' => $this->category,
            'collection' => $this->collection,
            'gender' => $this->gender,
            'material' => $this->material,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'availability' => $this->availability,
            'sale' => $this->sale,
            'featured' => $this->featured,
            'new' => $this->newArrival,
            'best' => $this->best,
            'attributeValueIds' => $this->attributeValueIds(),
        ];
    }

    protected function applyContextDefaults(): void
    {
        match ($this->context) {
            'category' => $this->category = (string) $this->categoryId(),
            'brand' => $this->brand = (string) $this->brandId(),
            'collection' => $this->collection = (string) $this->collectionId(),
            'sale' => $this->sale = true,
            'featured' => $this->featured = true,
            'new-arrivals' => $this->newArrival = true,
            'best-sellers' => $this->best = true,
            'gender' => $this->gender = (string) $this->slug,
            default => null,
        };
    }

    protected function pageTitle(): string
    {
        return match ($this->context) {
            'category' => Category::query()->whereKey($this->categoryId())->value('name') ?: __('Category'),
            'brand' => Brand::query()->whereKey($this->brandId())->value('name') ?: __('Brand'),
            'collection' => ProductCollection::query()->whereKey($this->collectionId())->value('name') ?: __('Collection'),
            'search' => __('Search results'),
            'sale' => __('Sale products'),
            'featured' => __('Featured products'),
            'new-arrivals' => __('New arrivals'),
            'best-sellers' => __('Best sellers'),
            'gender' => str((string) $this->slug)->title().' '.__('Shoes'),
            default => __('Shop footwear'),
        };
    }

    protected function pageDescription(): string
    {
        return match ($this->context) {
            'category' => Category::query()->whereKey($this->categoryId())->value('description') ?: __('Browse published products in this category.'),
            'brand' => Brand::query()->whereKey($this->brandId())->value('description') ?: __('Browse products from this brand.'),
            'collection' => ProductCollection::query()->whereKey($this->collectionId())->value('description') ?: __('Browse this curated collection.'),
            'search' => __('Search Amarvero products by name, brand, category, material, and SKU.'),
            'sale' => __('Discounted footwear currently available for storefront merchandising.'),
            'featured' => __('Products marked for featured merchandising.'),
            'new-arrivals' => __('Recently published footwear and new-arrival picks.'),
            'best-sellers' => __('Best-seller picks curated for discovery.'),
            default => __('Explore published Amarvero footwear with live filters and responsive product cards.'),
        };
    }

    protected function canonicalUrl(): string
    {
        return match ($this->context) {
            'category' => route('categories.show', ['slug' => $this->slug]),
            'brand' => route('brands.show', ['slug' => $this->slug]),
            'collection' => route('collections.show', ['slug' => $this->slug]),
            'search' => route('search'),
            'sale' => route('sale'),
            'featured' => route('featured'),
            'new-arrivals' => route('new-arrivals'),
            'best-sellers' => route('best-sellers'),
            'gender' => route('gender.show', ['slug' => $this->slug]),
            default => route('shop'),
        };
    }

    protected function shouldNoindexListing(): bool
    {
        return $this->context === 'search'
            || $this->sort !== 'newest'
            || $this->perPage !== 12
            || $this->viewMode !== 'grid'
            || $this->activeFilters() !== [];
    }

    /**
     * @return array<string, string>
     */
    protected function activeFilters(): array
    {
        $filters = [];

        if ($this->search !== '') {
            $filters['search'] = __('Search: :value', ['value' => $this->search]);
        }

        if ($this->brand !== '' && ! $this->contextLocksFilter('brand')) {
            $filters['brand'] = __('Brand: :value', ['value' => Brand::query()->whereKey((int) $this->brand)->value('name')]);
        }

        if ($this->category !== '' && ! $this->contextLocksFilter('category')) {
            $filters['category'] = __('Category: :value', ['value' => Category::query()->whereKey((int) $this->category)->value('name')]);
        }

        if ($this->collection !== '' && ! $this->contextLocksFilter('collection')) {
            $filters['collection'] = __('Collection: :value', ['value' => ProductCollection::query()->whereKey((int) $this->collection)->value('name')]);
        }

        if ($this->gender !== '' && ! $this->contextLocksFilter('gender')) {
            $filters['gender'] = __('Gender: :value', ['value' => str($this->gender)->title()]);
        }

        if ($this->material !== '') {
            $filters['material'] = __('Material: :value', ['value' => $this->material]);
        }

        if ($this->minPrice !== '' || $this->maxPrice !== '') {
            $filters['price'] = __('Price filtered');
        }

        if ($this->availability !== '') {
            $filters['availability'] = str($this->availability)->replace('_', ' ')->title()->toString();
        }

        foreach (['sale' => $this->sale, 'featured' => $this->featured, 'new' => $this->newArrival, 'best' => $this->best] as $key => $active) {
            if ($active && ! $this->contextLocksFilter($key)) {
                $filters[$key] = str($key)->replace('_', ' ')->title()->toString();
            }
        }

        if ($this->attributeValueIds() !== []) {
            $filters['attributes'] = __('Attribute options');
        }

        return $filters;
    }

    /**
     * @return array<string, string>
     */
    protected function sortOptions(): array
    {
        return [
            'featured' => __('Featured'),
            'newest' => __('Newest'),
            'oldest' => __('Oldest'),
            'price_asc' => __('Price low to high'),
            'price_desc' => __('Price high to low'),
            'best_selling' => __('Best selling'),
            'rating' => __('Highest rated'),
            'name_asc' => __('Name A to Z'),
            'name_desc' => __('Name Z to A'),
            'discount' => __('Largest discount'),
        ];
    }

    protected function safePerPage(): int
    {
        return in_array($this->perPage, [12, 24, 36], true) ? $this->perPage : 12;
    }

    /**
     * @return list<int>
     */
    protected function attributeValueIds(): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($this->attributeValueIds, static fn (mixed $id): bool => filled($id)),
        )));
    }

    protected function contextFromRouteName(): string
    {
        return match (request()->route()?->getName()) {
            'categories.show' => 'category',
            'brands.show' => 'brand',
            'collections.show' => 'collection',
            'search' => 'search',
            'sale' => 'sale',
            'featured' => 'featured',
            'new-arrivals' => 'new-arrivals',
            'best-sellers' => 'best-sellers',
            'gender.show' => 'gender',
            default => 'shop',
        };
    }

    protected function slugFromRoute(): ?string
    {
        $slug = request()->route('slug');

        return is_scalar($slug) ? (string) $slug : null;
    }

    protected function categoryId(): int
    {
        $id = Category::query()
            ->where('is_active', true)
            ->where('slug', $this->slug)
            ->value('id');

        abort_if($id === null, 404);

        return (int) $id;
    }

    protected function brandId(): int
    {
        $id = Brand::query()
            ->where('is_active', true)
            ->where('slug', $this->slug)
            ->value('id');

        abort_if($id === null, 404);

        return (int) $id;
    }

    protected function collectionId(): int
    {
        $id = ProductCollection::query()
            ->where('is_active', true)
            ->where('slug', $this->slug)
            ->value('id');

        abort_if($id === null, 404);

        return (int) $id;
    }

    protected function contextLocksFilter(string $filter): bool
    {
        return match ($this->context) {
            'brand' => $filter === 'brand',
            'category' => $filter === 'category',
            'collection' => $filter === 'collection',
            'gender' => $filter === 'gender',
            'sale' => $filter === 'sale',
            'featured' => $filter === 'featured',
            'new-arrivals' => $filter === 'new',
            'best-sellers' => $filter === 'best',
            default => false,
        };
    }
}
