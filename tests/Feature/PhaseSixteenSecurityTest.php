<?php

use App\Enums\ProductStatus;
use App\Livewire\Account\ReviewManager;
use App\Livewire\Storefront\CheckoutPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\User;
use App\Support\Security\SecurityRateLimits;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function phaseSixteenCheckoutFixture(): array
{
    $product = Product::factory()->create([
        'name' => 'Security Runner',
        'slug' => 'security-runner',
        'status' => ProductStatus::Published,
        'regular_price' => 3000,
        'published_at' => now()->subHour(),
    ]);

    $variant = ProductVariant::factory()
        ->for($product)
        ->create([
            'sku' => 'SEC-RUN-41',
            'stock_quantity' => 6,
            'reserved_quantity' => 0,
        ]);

    $shippingZone = ShippingZone::query()->create([
        'name' => 'Security zone',
        'countries' => ['BD'],
        'regions' => null,
        'is_active' => true,
    ]);

    $shippingMethod = ShippingMethod::query()->create([
        'shipping_zone_id' => $shippingZone->id,
        'name' => 'Security standard',
        'code' => 'security-standard',
        'price' => 100,
        'is_active' => true,
    ]);

    return compact('product', 'variant', 'shippingZone', 'shippingMethod');
}

test('web responses include security hardening headers', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
});

test('security sensitive routes use named throttle middleware', function () {
    expect(Route::getRoutes()->getByName('search')?->gatherMiddleware())->toContain('throttle:'.SecurityRateLimits::StorefrontSearch)
        ->and(Route::getRoutes()->getByName('cart.items.store')?->gatherMiddleware())->toContain('throttle:'.SecurityRateLimits::CartWrites)
        ->and(Route::getRoutes()->getByName('wishlist.items.store')?->gatherMiddleware())->toContain('throttle:'.SecurityRateLimits::WishlistWrites)
        ->and(Route::getRoutes()->getByName('checkout')?->gatherMiddleware())->toContain('throttle:'.SecurityRateLimits::Checkout)
        ->and(Route::getRoutes()->getByName('admin.dashboard')?->gatherMiddleware())->toContain('throttle:'.SecurityRateLimits::AdminRequests);
});

test('checkout coupon attempts are rate limited per shopper context', function () {
    $fixture = phaseSixteenCheckoutFixture();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 1,
    ])->assertSessionHas('status', 'Added to cart.');

    $component = Livewire::test(CheckoutPage::class)
        ->set('form.email', 'coupon-security@example.test');

    for ($attempt = 1; $attempt <= SecurityRateLimits::CouponMaxAttempts; $attempt++) {
        $component
            ->set('couponCode', 'missing-'.$attempt)
            ->call('applyCoupon')
            ->assertHasErrors(['couponCode']);
    }

    $component
        ->set('couponCode', 'missing-final')
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode'])
        ->assertSee('Too many coupon attempts.');
});

test('review submissions are rate limited per authenticated customer', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(ReviewManager::class);

    for ($attempt = 1; $attempt <= SecurityRateLimits::ReviewMaxAttempts; $attempt++) {
        $component
            ->call('save')
            ->assertHasErrors(['form.product_id']);
    }

    $component
        ->call('save')
        ->assertHasErrors(['reviews'])
        ->assertSee('Too many review submissions.');
});
