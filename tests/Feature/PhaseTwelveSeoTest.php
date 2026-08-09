<?php

use App\Enums\ContentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\StaticPage;
use App\Models\User;
use App\Support\Settings\SettingsManager;

function phaseTwelveProductFixture(array $overrides = []): array
{
    $brand = Brand::query()->create([
        'name' => 'SEO Studio',
        'slug' => 'seo-studio',
        'description' => 'SEO-ready footwear brand.',
    ]);

    $category = Category::query()->create([
        'name' => 'SEO Sneakers',
        'slug' => 'seo-sneakers',
        'description' => 'Sneakers prepared for metadata tests.',
    ]);

    $product = Product::query()->create(array_merge([
        'brand_id' => $brand->id,
        'name' => 'Structured Runner',
        'slug' => 'structured-runner',
        'base_sku' => 'SEO-RUN',
        'short_description' => 'A structured-data ready running shoe.',
        'description' => 'Detailed product copy that can safely become a meta description.',
        'seo_title' => 'Structured Runner SEO',
        'seo_description' => 'Structured Runner includes Product, Offer, and AggregateRating schema.',
        'status' => ProductStatus::Published,
        'regular_price' => 5200,
        'sale_price' => 4700,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
        'published_at' => now()->subHour(),
    ], $overrides));
    $product->categories()->attach($category);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SEO-RUN-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 6,
    ]);

    ProductImage::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'path' => 'https://cdn.example.test/structured-runner.jpg',
        'alt_text' => 'Structured Runner shoe side profile',
        'is_primary' => true,
    ]);

    ProductReview::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 5,
        'title' => 'Strong fit',
        'body' => 'Comfortable and durable for daily use.',
        'status' => ReviewStatus::Approved,
        'is_verified_purchase' => true,
        'approved_at' => now(),
    ]);

    return compact('brand', 'category', 'product', 'variant');
}

test('home page renders canonical metadata and organization structured data from settings', function () {
    $settings = app(SettingsManager::class);
    $settings->save('brand.name', 'Amarvero Studio');
    $settings->save('brand.logo_path', 'brand/logo.png');
    $settings->save('brand.favicon_path', 'brand/favicon.png');
    $settings->save('contact.email', 'support@example.test');
    $settings->save('seo.default_description', 'Default Amarvero SEO description for storefront pages.');
    $settings->save('seo.open_graph_image', 'seo/open-graph.jpg');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Shop Amarvero footwear', false)
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:image" content="'.url('/storage/seo/open-graph.jpg').'">', false)
        ->assertSee('<link rel="icon" href="'.url('/storage/brand/favicon.png').'"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('SearchAction', false)
        ->assertSee('support@example.test', false);
});

test('product detail pages render product offer and real aggregate rating structured data', function () {
    $fixture = phaseTwelveProductFixture();
    $product = $fixture['product'];

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertSee('<meta property="og:type" content="product">', false)
        ->assertSee('<link rel="canonical" href="'.route('products.show', ['product' => $product->slug]).'">', false)
        ->assertSee('<meta property="og:image" content="https://cdn.example.test/structured-runner.jpg">', false)
        ->assertSee('"@type":"Product"', false)
        ->assertSee('"@type":"Offer"', false)
        ->assertSee('"price":"4700.00"', false)
        ->assertSee('https://schema.org/InStock', false)
        ->assertSee('"@type":"AggregateRating"', false)
        ->assertSee('"reviewCount":1', false);
});

test('clean listing pages are indexable while filtered search variants are noindexed', function () {
    $fixture = phaseTwelveProductFixture();
    $category = $fixture['category'];

    $this->get(route('categories.show', ['slug' => $category->slug]))
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('categories.show', ['slug' => $category->slug]).'">', false)
        ->assertSee('"@type":"BreadcrumbList"', false);

    $this->get(route('shop', ['q' => 'runner']))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('shop').'">', false);
});

test('sitemap and robots expose public crawl targets and exclude private or draft surfaces', function () {
    $fixture = phaseTwelveProductFixture();
    $publishedProduct = $fixture['product'];

    Product::query()->create([
        'name' => 'Draft Runner',
        'slug' => 'draft-runner',
        'status' => ProductStatus::Draft,
        'regular_price' => 3000,
    ]);

    ProductCollection::query()->create([
        'name' => 'SEO Collection',
        'slug' => 'seo-collection',
        'is_active' => true,
    ]);

    StaticPage::query()->create([
        'title' => 'Shipping Policy',
        'slug' => 'shipping-policy',
        'body' => 'Shipping policy content.',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'seo_title' => 'Shipping Policy',
        'seo_description' => 'Shipping policy SEO description.',
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('products.show', ['product' => $publishedProduct->slug]), false)
        ->assertSee(route('pages.show', ['page' => 'shipping-policy']), false)
        ->assertSee(route('collections.show', ['slug' => 'seo-collection']), false)
        ->assertDontSee('draft-runner')
        ->assertDontSee('/admin');

    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /checkout')
        ->assertSee('Sitemap: '.route('sitemap'));
});
