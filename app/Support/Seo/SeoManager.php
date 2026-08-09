<?php

namespace App\Support\Seo;

use App\Enums\ContentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StaticPage;
use App\Support\Settings\SettingsManager;
use App\Support\Storefront\ProductCatalog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @phpstan-type SeoPayload array{title: string, description: string, canonical: string, image: string|null, type: string, robots: string, favicon: string|null, structured_data: list<array<string, mixed>>}
 * @phpstan-type SitemapUrl array{loc: string, lastmod: string|null, changefreq: string, priority: string}
 */
class SeoManager
{
    public function __construct(protected SettingsManager $settingsManager) {}

    /**
     * @param  list<array<string, mixed>>  $structuredData
     * @return SeoPayload
     */
    public function meta(
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        string $type = 'website',
        string $robots = 'index, follow',
        array $structuredData = [],
    ): array {
        $settings = $this->settingsManager->values();
        $brandName = $this->settingString($settings, 'brand.name', config('app.name', 'Amarvero'));
        $defaultTitle = $this->settingString($settings, 'seo.default_title', __('Premium footwear for daily movement'));
        $defaultDescription = $this->settingString($settings, 'seo.default_description', __('Explore Amarvero footwear, secure checkout, customer accounts, and dynamic product discovery.'));
        $fallbackImage = $this->settingString($settings, 'seo.open_graph_image', '');
        $favicon = $this->settingString($settings, 'brand.favicon_path', '');

        $cleanTitle = $this->cleanText($title ?: $defaultTitle, 70);
        $cleanDescription = $this->cleanText($description ?: $defaultDescription, 160);

        if (! Str::contains(Str::lower($cleanTitle), Str::lower($brandName))) {
            $cleanTitle = $this->cleanText($cleanTitle.' - '.$brandName, 70);
        }

        return [
            'title' => $cleanTitle,
            'description' => $cleanDescription,
            'canonical' => $this->absoluteUrl($canonical ?: url()->current()),
            'image' => $this->mediaUrl($image ?: $fallbackImage),
            'type' => $type,
            'robots' => $robots,
            'favicon' => $this->mediaUrl($favicon),
            'structured_data' => $structuredData,
        ];
    }

    /**
     * @return SeoPayload
     */
    public function home(): array
    {
        return $this->meta(
            title: __('Premium footwear for daily movement'),
            description: __('Shop Amarvero footwear with responsive product discovery, secure checkout, dynamic merchandising, and customer account tools.'),
            canonical: route('home'),
            type: 'website',
            structuredData: [
                $this->organizationStructuredData(),
                $this->websiteStructuredData(),
            ],
        );
    }

    /**
     * @return SeoPayload
     */
    public function page(StaticPage $page): array
    {
        $seoMeta = $page->getAttribute('seo_meta');
        $seoMeta = is_array($seoMeta) ? $seoMeta : [];
        $image = is_scalar($seoMeta['image'] ?? null) ? (string) $seoMeta['image'] : null;
        $robots = is_scalar($seoMeta['robots'] ?? null) ? (string) $seoMeta['robots'] : 'index, follow';

        return $this->meta(
            title: $page->seo_title ?: $page->title,
            description: $page->seo_description ?: $page->body,
            canonical: route('pages.show', ['page' => $page->slug]),
            image: $image,
            type: 'article',
            robots: $robots,
            structuredData: [
                $this->breadcrumbStructuredData([
                    ['name' => __('Home'), 'url' => route('home')],
                    ['name' => $page->title, 'url' => route('pages.show', ['page' => $page->slug])],
                ]),
            ],
        );
    }

    /**
     * @return SeoPayload
     */
    public function listing(string $title, string $description, string $canonical, bool $noindex = false): array
    {
        return $this->meta(
            title: $title,
            description: $description,
            canonical: $canonical,
            type: 'website',
            robots: $noindex ? 'noindex, follow' : 'index, follow',
            structuredData: [
                $this->breadcrumbStructuredData([
                    ['name' => __('Home'), 'url' => route('home')],
                    ['name' => $title, 'url' => $canonical],
                ]),
            ],
        );
    }

    /**
     * @return SeoPayload
     */
    public function product(Product $product, ProductCatalog $catalog, ?ProductVariant $selectedVariant = null): array
    {
        $primaryImage = $catalog->primaryImage($product);
        $image = $primaryImage instanceof ProductImage ? $catalog->mediaUrl($primaryImage->path, $primaryImage->disk) : null;

        return $this->meta(
            title: $product->seo_title ?: $product->name,
            description: $product->seo_description ?: ($product->short_description ?: (string) $product->description),
            canonical: route('products.show', ['product' => $product->slug]),
            image: $image,
            type: 'product',
            structuredData: [
                $this->breadcrumbStructuredData($this->productBreadcrumbs($product)),
                $this->productStructuredData($product, $catalog, $selectedVariant),
            ],
        );
    }

    /**
     * @return list<SitemapUrl>
     */
    public function sitemapUrls(): array
    {
        $urls = [
            $this->sitemapUrl(route('home'), null, 'daily', '1.0'),
            $this->sitemapUrl(route('shop'), null, 'daily', '0.9'),
            $this->sitemapUrl(route('sale'), null, 'daily', '0.8'),
            $this->sitemapUrl(route('featured'), null, 'weekly', '0.8'),
            $this->sitemapUrl(route('new-arrivals'), null, 'daily', '0.8'),
            $this->sitemapUrl(route('best-sellers'), null, 'weekly', '0.8'),
        ];

        Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Category $category) use (&$urls): void {
                $urls[] = $this->sitemapUrl(route('categories.show', ['slug' => $category->slug]), $category->updated_at, 'weekly', '0.7');
            });

        Brand::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Brand $brand) use (&$urls): void {
                $urls[] = $this->sitemapUrl(route('brands.show', ['slug' => $brand->slug]), $brand->updated_at, 'weekly', '0.7');
            });

        ProductCollection::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (ProductCollection $collection) use (&$urls): void {
                $urls[] = $this->sitemapUrl(route('collections.show', ['slug' => $collection->slug]), $collection->updated_at, 'weekly', '0.7');
            });

        Product::query()
            ->published()
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use (&$urls): void {
                $urls[] = $this->sitemapUrl(route('products.show', ['product' => $product->slug]), $product->updated_at, 'daily', '0.9');
            });

        $this->publishedPagesQuery()
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (StaticPage $page) use (&$urls): void {
                $urls[] = $this->sitemapUrl(route('pages.show', ['page' => $page->slug]), $page->updated_at, 'monthly', '0.6');
            });

        return $urls;
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationStructuredData(): array
    {
        $settings = $this->settingsManager->values();
        $logo = $this->settingString($settings, 'brand.logo_path', '');
        $email = $this->settingString($settings, 'contact.email', '');
        $phone = $this->settingString($settings, 'contact.support_phone', '');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->settingString($settings, 'brand.name', config('app.name', 'Amarvero')),
            'url' => route('home'),
        ];

        if ($logo !== '') {
            $data['logo'] = $this->mediaUrl($logo);
        }

        if ($email !== '' || $phone !== '') {
            $data['contactPoint'] = array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $email !== '' ? $email : null,
                'telephone' => $phone !== '' ? $phone : null,
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function websiteStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->settingString($this->settingsManager->values(), 'brand.name', config('app.name', 'Amarvero')),
            'url' => route('home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbStructuredData(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $this->absoluteUrl($item['url']),
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productStructuredData(Product $product, ProductCatalog $catalog, ?ProductVariant $selectedVariant): array
    {
        $images = $product->images
            ->map(fn (ProductImage $image): ?string => $catalog->mediaUrl($image->path, $image->disk))
            ->filter()
            ->values()
            ->all();

        $price = $selectedVariant?->price_override === null
            ? $catalog->effectivePrice($product)
            : (float) $selectedVariant->price_override;

        $available = $selectedVariant instanceof ProductVariant
            ? $selectedVariant->availableQuantity()
            : $catalog->availableQuantity($product);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $this->cleanText($product->seo_description ?: ($product->short_description ?: (string) $product->description), 500),
            'sku' => $selectedVariant?->sku ?: ($product->base_sku ?: 'product-'.$product->id),
            'url' => route('products.show', ['product' => $product->slug]),
        ];

        if ($images !== []) {
            $data['image'] = array_map(fn (string $image): string => $this->absoluteUrl($image), $images);
        }

        if ($product->brand) {
            $data['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        if ($price !== null) {
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => route('products.show', ['product' => $product->slug]),
                'priceCurrency' => 'BDT',
                'price' => number_format($price, 2, '.', ''),
                'availability' => $available > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ];
        }

        $reviewCount = (int) ($product->getAttribute('approved_reviews_count') ?? 0);
        $averageRating = (float) ($product->getAttribute('approved_reviews_avg_rating') ?? 0);

        if ($reviewCount > 0 && $averageRating > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($averageRating, 1, '.', ''),
                'reviewCount' => $reviewCount,
            ];
        }

        return $data;
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    protected function productBreadcrumbs(Product $product): array
    {
        $breadcrumbs = [
            ['name' => __('Home'), 'url' => route('home')],
            ['name' => __('Shop'), 'url' => route('shop')],
        ];

        $category = $product->categories->first();

        if ($category instanceof Category) {
            $breadcrumbs[] = ['name' => $category->name, 'url' => route('categories.show', ['slug' => $category->slug])];
        }

        $breadcrumbs[] = ['name' => $product->name, 'url' => route('products.show', ['product' => $product->slug])];

        return $breadcrumbs;
    }

    /**
     * @return Builder<StaticPage>
     */
    protected function publishedPagesQuery(): Builder
    {
        return StaticPage::query()
            ->where('status', ContentStatus::Published)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @return SitemapUrl
     */
    protected function sitemapUrl(string $url, mixed $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $this->absoluteUrl($url),
            'lastmod' => $lastmod instanceof DateTimeInterface ? $lastmod->format(DATE_ATOM) : null,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function settingString(array $settings, string $key, mixed $fallback): string
    {
        $value = $settings[$key] ?? $fallback;

        return is_scalar($value) ? trim((string) $value) : '';
    }

    protected function cleanText(string $value, int $limit): string
    {
        $clean = Str::of(strip_tags($value))
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit($limit, '')
            ->toString();

        return $clean === '' ? config('app.name', 'Amarvero') : $clean;
    }

    protected function mediaUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = (string) $path;

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $this->absoluteUrl($path);
        }

        return $this->absoluteUrl(Storage::disk('public')->url($path));
    }

    protected function absoluteUrl(string $url): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }
}
