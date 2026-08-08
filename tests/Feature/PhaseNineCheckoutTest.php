<?php

use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\CheckoutPage;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Livewire\Livewire;

function phaseNineCheckoutFixture(): array
{
    $brand = Brand::query()->create([
        'name' => 'Checkout Studio',
        'slug' => 'checkout-studio',
    ]);

    $category = Category::query()->create([
        'name' => 'Checkout Sneakers',
        'slug' => 'checkout-sneakers',
    ]);

    $product = Product::query()->create([
        'brand_id' => $brand->id,
        'name' => 'Checkout Runner',
        'slug' => 'checkout-runner',
        'base_sku' => 'COR',
        'short_description' => 'Checkout-ready sneaker.',
        'description' => 'A checkout fixture with orderable stock.',
        'status' => ProductStatus::Published,
        'regular_price' => 3000,
        'sale_price' => 2500,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
        'published_at' => now()->subHour(),
    ]);
    $product->categories()->attach($category);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'COR-BLK-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 6,
        'reserved_quantity' => 1,
    ]);

    ProductImage::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'path' => 'https://example.test/images/checkout-runner.jpg',
        'alt_text' => 'Checkout Runner black profile',
        'is_primary' => true,
    ]);

    $shippingZone = ShippingZone::query()->create([
        'name' => 'Bangladesh',
        'countries' => ['BD'],
        'regions' => null,
        'sort_order' => 1,
    ]);

    $shippingMethod = ShippingMethod::query()->create([
        'shipping_zone_id' => $shippingZone->id,
        'name' => 'Dhaka standard',
        'code' => 'dhaka-standard',
        'price' => 120,
        'estimated_days_min' => 2,
        'estimated_days_max' => 4,
    ]);

    $coupon = Coupon::query()->create([
        'code' => 'SAVE500',
        'name' => 'Save 500',
        'type' => CouponType::Cart,
        'discount_type' => DiscountType::Fixed,
        'value' => 500,
        'minimum_order_amount' => 1000,
        'total_usage_limit' => 10,
        'per_customer_usage_limit' => 1,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    return compact('brand', 'category', 'product', 'variant', 'shippingZone', 'shippingMethod', 'coupon');
}

test('guests can apply coupons choose shipping and place checkout orders', function () {
    $fixture = phaseNineCheckoutFixture();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 2,
    ])->assertSessionHas('status', 'Added to cart.');

    $component = Livewire::test(CheckoutPage::class)
        ->assertSee('Checkout Runner')
        ->set('form.customer_name', 'Nadia Customer')
        ->set('form.email', 'nadia@example.test')
        ->set('form.phone', '+8801700000000')
        ->set('form.line_one', 'House 12, Road 4')
        ->set('form.area', 'Banani')
        ->set('form.city', 'Dhaka')
        ->set('form.region', 'Dhaka')
        ->set('shippingMethodId', $fixture['shippingMethod']->id)
        ->set('paymentMethod', PaymentMethod::CashOnDelivery->value)
        ->set('couponCode', 'save500')
        ->call('applyCoupon')
        ->assertHasNoErrors(['couponCode'])
        ->assertSee('Coupon SAVE500 applied.')
        ->assertSee('BDT 4,620.00')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->with(['items', 'addresses', 'payments.events', 'statusEvents', 'shippingMethod'])->firstOrFail();

    $component->assertRedirect(route('checkout.thank-you', ['order' => $order->order_number]));

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and((float) $order->subtotal)->toBe(5000.0)
        ->and((float) $order->discount_total)->toBe(500.0)
        ->and((float) $order->shipping_total)->toBe(120.0)
        ->and((float) $order->grand_total)->toBe(4620.0)
        ->and($order->shippingMethod?->is($fixture['shippingMethod']))->toBeTrue()
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()?->product_name)->toBe('Checkout Runner')
        ->and($order->addresses)->toHaveCount(2)
        ->and(Payment::query()->count())->toBe(1)
        ->and(Payment::query()->first()?->method)->toBe(PaymentMethod::CashOnDelivery)
        ->and(PaymentEvent::query()->count())->toBe(1)
        ->and(OrderStatusEvent::query()->count())->toBe(1)
        ->and(CouponRedemption::query()->count())->toBe(1)
        ->and((float) CouponRedemption::query()->first()?->discount_amount)->toBe(500.0)
        ->and($fixture['coupon']->refresh()->usage_count)->toBe(1)
        ->and($fixture['variant']->refresh()->stock_quantity)->toBe(4)
        ->and(InventoryMovement::query()->first()?->type)->toBe(InventoryMovementType::Sale)
        ->and(InventoryMovement::query()->first()?->quantity)->toBe(-2)
        ->and(Cart::query()->first()?->status)->toBe(CartStatus::Converted);

    $this->get(route('checkout.thank-you', ['order' => $order->order_number]))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('BDT 4,620.00');
});

test('invalid coupons are rejected without attaching them to the cart', function () {
    $fixture = phaseNineCheckoutFixture();

    Coupon::query()->create([
        'code' => 'EXPIRED',
        'name' => 'Expired',
        'type' => CouponType::Cart,
        'discount_type' => DiscountType::Fixed,
        'value' => 100,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDay(),
    ]);

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 1,
    ]);

    Livewire::test(CheckoutPage::class)
        ->set('form.email', 'coupon@example.test')
        ->set('couponCode', 'expired')
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode']);

    expect(Cart::query()->first()?->coupon_id)->toBeNull()
        ->and(CouponRedemption::query()->count())->toBe(0);
});

test('checkout blocks orders when stock changes after cart creation', function () {
    $fixture = phaseNineCheckoutFixture();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 2,
    ]);

    $fixture['variant']->forceFill([
        'stock_quantity' => 1,
        'reserved_quantity' => 0,
    ])->save();

    Livewire::test(CheckoutPage::class)
        ->set('form.customer_name', 'Stock Customer')
        ->set('form.email', 'stock@example.test')
        ->set('form.phone', '+8801700000000')
        ->set('form.line_one', 'Warehouse Road')
        ->set('form.city', 'Dhaka')
        ->set('shippingMethodId', $fixture['shippingMethod']->id)
        ->call('placeOrder')
        ->assertHasErrors(['cart']);

    expect(Order::query()->count())->toBe(0)
        ->and(Cart::query()->first()?->status)->toBe(CartStatus::Active)
        ->and($fixture['variant']->refresh()->stock_quantity)->toBe(1);
});

test('guest order confirmation is session scoped', function () {
    $fixture = phaseNineCheckoutFixture();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 1,
    ]);

    Livewire::test(CheckoutPage::class)
        ->set('form.customer_name', 'Guest Customer')
        ->set('form.email', 'guest@example.test')
        ->set('form.phone', '+8801700000000')
        ->set('form.line_one', 'Session Lane')
        ->set('form.city', 'Dhaka')
        ->set('shippingMethodId', $fixture['shippingMethod']->id)
        ->call('placeOrder');

    $order = Order::query()->firstOrFail();

    session()->forget('checkout.last_order_id');

    $this->get(route('checkout.thank-you', ['order' => $order->order_number]))
        ->assertNotFound();
});
