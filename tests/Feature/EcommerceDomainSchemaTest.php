<?php

use App\Enums\CartStatus;
use App\Enums\ContentStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SizeGuide;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('phase one ecommerce tables are present', function () {
    $tables = [
        'brands',
        'categories',
        'product_collections',
        'product_attributes',
        'attribute_values',
        'products',
        'product_variants',
        'product_images',
        'inventory_movements',
        'carts',
        'cart_items',
        'wishlists',
        'orders',
        'order_items',
        'payments',
        'coupons',
        'coupon_redemptions',
        'product_reviews',
        'hero_slides',
        'homepage_sections',
        'site_settings',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue($table.' table is missing');
    }

    expect(Schema::hasColumns('products', [
        'brand_id',
        'slug',
        'status',
        'regular_price',
        'published_at',
        'seo_title',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('orders', [
            'order_number',
            'status',
            'payment_status',
            'grand_total',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('coupons', [
            'code',
            'discount_type',
            'minimum_order_amount',
            'per_customer_usage_limit',
        ]))->toBeTrue();
});

test('catalog models can persist variants and merchandising relationships', function () {
    $brand = Brand::query()->create([
        'name' => 'Amarvero Studio',
        'slug' => 'amarvero-studio',
    ]);

    $category = Category::query()->create([
        'name' => 'Sneakers',
        'slug' => 'sneakers',
    ]);

    $collection = ProductCollection::query()->create([
        'name' => 'Just Landed',
        'slug' => 'just-landed',
    ]);

    $sizeGuide = SizeGuide::query()->create([
        'name' => 'Everyday Sneakers',
        'slug' => 'everyday-sneakers',
        'measurements' => ['EU' => ['42' => '26.5cm']],
    ]);

    $attribute = ProductAttribute::query()->create([
        'name' => 'Color',
        'slug' => 'color',
        'is_variant_option' => true,
    ]);

    $attributeValue = AttributeValue::query()->create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Black',
        'slug' => 'black',
    ]);

    $product = Product::query()->create([
        'brand_id' => $brand->id,
        'name' => 'Court Runner',
        'slug' => 'court-runner',
        'base_sku' => 'CR',
        'status' => ProductStatus::Published,
        'regular_price' => 2490,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'CR-BLK-42',
        'option_label' => 'Black / 42',
        'stock_quantity' => 12,
        'reserved_quantity' => 2,
    ]);

    ProductImage::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'path' => 'products/court-runner.webp',
        'is_primary' => true,
    ]);

    $product->categories()->attach($category, ['sort_order' => 1]);
    $product->collections()->attach($collection, ['sort_order' => 2]);
    $product->attributeValues()->attach($attributeValue);
    $product->sizeGuides()->attach($sizeGuide);
    $variant->attributeValues()->attach($attributeValue);

    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Published)
        ->and($product->brand->is($brand))->toBeTrue()
        ->and($product->categories)->toHaveCount(1)
        ->and($product->collections)->toHaveCount(1)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->images)->toHaveCount(1)
        ->and($variant->availableQuantity())->toBe(10);
});

test('commerce models can persist cart order coupon payment and review relationships', function () {
    $user = User::factory()->create();
    $brand = Brand::query()->create(['name' => 'Amarvero', 'slug' => 'amarvero']);
    $product = Product::query()->create([
        'brand_id' => $brand->id,
        'name' => 'Penny Loafer',
        'slug' => 'penny-loafer',
        'status' => ProductStatus::Published,
        'regular_price' => 2990,
        'published_at' => now(),
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'PL-BRN-41',
        'stock_quantity' => 5,
    ]);
    $campaign = Campaign::query()->create([
        'name' => 'Launch Offer',
        'slug' => 'launch-offer',
        'status' => ContentStatus::Published,
    ]);
    $coupon = Coupon::query()->create([
        'campaign_id' => $campaign->id,
        'code' => 'LAUNCH10',
        'name' => 'Launch 10',
        'type' => CouponType::Cart,
        'discount_type' => DiscountType::Fixed,
        'value' => 300,
        'is_active' => true,
    ]);
    $zone = ShippingZone::query()->create(['name' => 'Bangladesh']);
    $shippingMethod = ShippingMethod::query()->create([
        'shipping_zone_id' => $zone->id,
        'name' => 'Standard Dhaka',
        'code' => 'standard-dhaka',
        'price' => 80,
    ]);
    $cart = Cart::query()->create([
        'user_id' => $user->id,
        'coupon_id' => $coupon->id,
        'status' => CartStatus::Active,
    ]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_snapshot' => 2990,
    ]);
    $order = Order::query()->create([
        'user_id' => $user->id,
        'coupon_id' => $coupon->id,
        'shipping_method_id' => $shippingMethod->id,
        'order_number' => 'AMV-1001',
        'customer_name' => $user->name,
        'email' => $user->email,
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Pending,
        'subtotal' => 2990,
        'discount_total' => 300,
        'shipping_total' => 80,
        'grand_total' => 2770,
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_name' => $variant->option_label,
        'sku' => $variant->sku,
        'quantity' => 1,
        'unit_price' => 2990,
        'discount_total' => 300,
        'line_total' => 2690,
    ]);
    Payment::query()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CashOnDelivery,
        'status' => PaymentStatus::Pending,
        'amount' => 2770,
    ]);
    ProductReview::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'user_id' => $user->id,
        'order_id' => $order->id,
        'rating' => 5,
        'body' => 'Comfortable from day one.',
        'status' => ReviewStatus::Approved,
        'is_verified_purchase' => true,
    ]);

    expect($user->carts)->toHaveCount(1)
        ->and($user->orders)->toHaveCount(1)
        ->and($cart->items)->toHaveCount(1)
        ->and($order->items)->toHaveCount(1)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and($product->reviews)->toHaveCount(1);
});
