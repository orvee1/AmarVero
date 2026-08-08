<?php

use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Storefront\ProductListing;
use App\Livewire\Storefront\ProductShow;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\SizeGuide;
use App\Models\User;
use Livewire\Livewire;

function phaseSevenCatalogFixture(): array
{
    $urbanBrand = Brand::query()->create([
        'name' => 'Urban Studio',
        'slug' => 'urban-studio',
        'description' => 'City-ready footwear.',
    ]);

    $officeBrand = Brand::query()->create([
        'name' => 'Office Lab',
        'slug' => 'office-lab',
    ]);

    $sneakers = Category::query()->create([
        'name' => 'Sneakers',
        'slug' => 'sneakers',
        'description' => 'Daily sneaker rotation.',
    ]);

    $loafers = Category::query()->create([
        'name' => 'Loafers',
        'slug' => 'loafers',
    ]);

    $collection = ProductCollection::query()->create([
        'name' => 'Just Landed',
        'slug' => 'just-landed',
        'description' => 'New arrivals and fresh edits.',
    ]);

    $color = ProductAttribute::query()->create([
        'name' => 'Color',
        'slug' => 'color',
        'is_variant_option' => true,
    ]);

    $size = ProductAttribute::query()->create([
        'name' => 'Size',
        'slug' => 'size',
        'is_variant_option' => true,
    ]);

    $black = AttributeValue::query()->create([
        'product_attribute_id' => $color->id,
        'value' => 'Black',
        'slug' => 'black',
        'color_hex' => '#111827',
    ]);

    $tan = AttributeValue::query()->create([
        'product_attribute_id' => $color->id,
        'value' => 'Tan',
        'slug' => 'tan',
        'color_hex' => '#b45309',
    ]);

    $size41 = AttributeValue::query()->create([
        'product_attribute_id' => $size->id,
        'value' => 'EU 41',
        'slug' => 'eu-41',
    ]);

    $size42 = AttributeValue::query()->create([
        'product_attribute_id' => $size->id,
        'value' => 'EU 42',
        'slug' => 'eu-42',
    ]);

    $runner = Product::query()->create([
        'brand_id' => $urbanBrand->id,
        'name' => 'City Runner',
        'slug' => 'city-runner',
        'base_sku' => 'CR',
        'short_description' => 'Responsive sneakers for long city days.',
        'description' => 'Breathable upper, stable sole, and cushioned stride.',
        'status' => ProductStatus::Published,
        'gender' => 'men',
        'material' => 'Knit mesh',
        'care_instructions' => 'Wipe clean after wear.',
        'regular_price' => 2990,
        'sale_price' => 2590,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
        'published_at' => now()->subHour(),
        'is_featured' => true,
        'is_new_arrival' => true,
        'is_best_seller' => true,
    ]);

    $runner->categories()->attach($sneakers, ['sort_order' => 1]);
    $runner->collections()->attach($collection, ['sort_order' => 1]);
    $runner->attributeValues()->sync([$black->id, $size41->id, $size42->id]);

    $runnerVariant41 = ProductVariant::query()->create([
        'product_id' => $runner->id,
        'sku' => 'CR-BLK-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 8,
        'reserved_quantity' => 1,
        'low_stock_threshold' => 2,
        'sort_order' => 1,
    ]);
    $runnerVariant41->attributeValues()->sync([$black->id, $size41->id]);

    $runnerVariant42 = ProductVariant::query()->create([
        'product_id' => $runner->id,
        'sku' => 'CR-BLK-42',
        'option_label' => 'Black / EU 42',
        'stock_quantity' => 4,
        'sort_order' => 2,
    ]);
    $runnerVariant42->attributeValues()->sync([$black->id, $size42->id]);

    ProductImage::query()->create([
        'product_id' => $runner->id,
        'product_variant_id' => $runnerVariant41->id,
        'path' => 'https://example.test/images/city-runner-black.jpg',
        'alt_text' => 'City Runner black side profile',
        'is_primary' => true,
    ]);

    ProductImage::query()->create([
        'product_id' => $runner->id,
        'path' => 'https://example.test/images/city-runner-sole.jpg',
        'alt_text' => 'City Runner sole detail',
        'sort_order' => 2,
    ]);

    $sizeGuide = SizeGuide::query()->create([
        'brand_id' => $urbanBrand->id,
        'category_id' => $sneakers->id,
        'name' => 'Runner Size Guide',
        'slug' => 'runner-size-guide',
        'content' => 'Measure feet at the end of the day.',
        'measurements' => [
            ['label' => 'EU 41', 'measurement' => '26 cm'],
            ['label' => 'EU 42', 'measurement' => '26.5 cm'],
        ],
    ]);
    $runner->sizeGuides()->attach($sizeGuide);

    $customer = User::factory()->create(['name' => 'Nadia Customer']);

    ProductReview::query()->create([
        'product_id' => $runner->id,
        'product_variant_id' => $runnerVariant41->id,
        'user_id' => $customer->id,
        'rating' => 5,
        'title' => 'Comfortable commute',
        'body' => 'Comfortable from day one.',
        'status' => ReviewStatus::Approved,
        'is_verified_purchase' => true,
        'approved_at' => now(),
    ]);

    ProductReview::query()->create([
        'product_id' => $runner->id,
        'rating' => 1,
        'body' => 'Pending moderation.',
        'status' => ReviewStatus::Pending,
    ]);

    $loafer = Product::query()->create([
        'brand_id' => $officeBrand->id,
        'name' => 'Office Loafer',
        'slug' => 'office-loafer',
        'base_sku' => 'OL',
        'status' => ProductStatus::Published,
        'gender' => 'women',
        'material' => 'Leather',
        'regular_price' => 3490,
        'published_at' => now()->subHour(),
    ]);
    $loafer->categories()->attach($loafers);
    $loafer->attributeValues()->sync([$tan->id, $size41->id]);

    $loaferVariant = ProductVariant::query()->create([
        'product_id' => $loafer->id,
        'sku' => 'OL-TAN-41',
        'option_label' => 'Tan / EU 41',
        'stock_quantity' => 0,
    ]);
    $loaferVariant->attributeValues()->sync([$tan->id, $size41->id]);

    $relatedRunner = Product::query()->create([
        'brand_id' => $urbanBrand->id,
        'name' => 'City Runner High',
        'slug' => 'city-runner-high',
        'base_sku' => 'CRH',
        'status' => ProductStatus::Published,
        'regular_price' => 3190,
        'published_at' => now()->subMinutes(30),
    ]);
    $relatedRunner->categories()->attach($sneakers);
    ProductVariant::query()->create([
        'product_id' => $relatedRunner->id,
        'sku' => 'CRH-BLK-42',
        'stock_quantity' => 3,
    ]);

    $futureSale = Product::query()->create([
        'brand_id' => $urbanBrand->id,
        'name' => 'Future Sale Runner',
        'slug' => 'future-sale-runner',
        'status' => ProductStatus::Published,
        'regular_price' => 2990,
        'sale_price' => 1990,
        'sale_starts_at' => now()->addDay(),
        'published_at' => now()->subHour(),
    ]);
    ProductVariant::query()->create([
        'product_id' => $futureSale->id,
        'sku' => 'FSR-41',
        'stock_quantity' => 5,
    ]);

    $draft = Product::query()->create([
        'name' => 'Hidden Draft',
        'slug' => 'hidden-draft',
        'status' => ProductStatus::Draft,
        'regular_price' => 1990,
    ]);

    return compact(
        'urbanBrand',
        'officeBrand',
        'sneakers',
        'loafers',
        'collection',
        'black',
        'tan',
        'size',
        'size41',
        'size42',
        'runner',
        'runnerVariant41',
        'runnerVariant42',
        'loafer',
        'relatedRunner',
        'futureSale',
        'draft',
    );
}

test('public storefront listing routes render published catalog contexts', function () {
    $fixture = phaseSevenCatalogFixture();

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('Shop footwear')
        ->assertSee('City Runner')
        ->assertSee('Office Loafer')
        ->assertDontSee('Hidden Draft');

    $this->get(route('categories.show', ['slug' => $fixture['sneakers']->slug]))
        ->assertOk()
        ->assertSee('Sneakers')
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer');

    $this->get(route('brands.show', ['slug' => $fixture['urbanBrand']->slug]))
        ->assertOk()
        ->assertSee('Urban Studio')
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer');

    $this->get(route('collections.show', ['slug' => $fixture['collection']->slug]))
        ->assertOk()
        ->assertSee('Just Landed')
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer');

    $this->get(route('sale'))
        ->assertOk()
        ->assertSee('City Runner')
        ->assertDontSee('Future Sale Runner');

    $this->get(route('new-arrivals'))
        ->assertOk()
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer');

    $this->get(route('gender.show', ['slug' => 'women']))
        ->assertOk()
        ->assertSee('Office Loafer')
        ->assertDontSee('City Runner');
});

test('livewire product listing supports search filters and availability', function () {
    $fixture = phaseSevenCatalogFixture();

    Livewire::test(ProductListing::class)
        ->assertSee('City Runner')
        ->assertSee('Office Loafer')
        ->set('search', 'loafer')
        ->assertSee('Office Loafer')
        ->assertDontSee('City Runner')
        ->set('search', '')
        ->set('brand', (string) $fixture['urbanBrand']->id)
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer')
        ->set('brand', '')
        ->set('attributeValueIds', [$fixture['black']->id])
        ->assertSee('City Runner')
        ->assertDontSee('Office Loafer')
        ->set('attributeValueIds', [])
        ->set('availability', 'out_of_stock')
        ->assertSee('Office Loafer')
        ->assertDontSee('City Runner');
});

test('product detail page renders variants stock size guide reviews and related products', function () {
    $fixture = phaseSevenCatalogFixture();

    $this->get(route('products.show', ['product' => $fixture['runner']->slug]))
        ->assertOk()
        ->assertSee('City Runner')
        ->assertSee('Urban Studio')
        ->assertSee('BDT 2,590.00')
        ->assertSee('Black')
        ->assertSee('EU 41')
        ->assertSee('CR-BLK-41')
        ->assertSee('7 available')
        ->assertSee('Runner Size Guide')
        ->assertSee('Comfortable from day one.')
        ->assertSee('City Runner High')
        ->assertDontSee('Pending moderation.');

    $this->get(route('products.show', ['product' => $fixture['draft']->slug]))
        ->assertNotFound();
});

test('product detail variant options update selected stock and sku', function () {
    $fixture = phaseSevenCatalogFixture();

    Livewire::test(ProductShow::class, ['product' => $fixture['runner']])
        ->assertSee('CR-BLK-41')
        ->assertSee('7 available')
        ->set('selectedOptions.'.$fixture['size']->id, $fixture['size42']->id)
        ->assertSee('CR-BLK-42')
        ->assertSee('4 available');
});
