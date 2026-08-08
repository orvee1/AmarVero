<?php

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\ProductShow;
use App\Livewire\Storefront\WishlistPage;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Auth\Events\Login;
use Livewire\Livewire;

function phaseEightCatalogFixture(): array
{
    $brand = Brand::query()->create([
        'name' => 'Cart Studio',
        'slug' => 'cart-studio',
    ]);

    $category = Category::query()->create([
        'name' => 'Cart Sneakers',
        'slug' => 'cart-sneakers',
    ]);

    $color = ProductAttribute::query()->create([
        'name' => 'Color',
        'slug' => 'color',
        'is_variant_option' => true,
    ]);

    $black = AttributeValue::query()->create([
        'product_attribute_id' => $color->id,
        'value' => 'Black',
        'slug' => 'black',
        'color_hex' => '#111827',
    ]);

    $product = Product::query()->create([
        'brand_id' => $brand->id,
        'name' => 'Merge Runner',
        'slug' => 'merge-runner',
        'base_sku' => 'MR',
        'short_description' => 'Cart-ready sneaker.',
        'description' => 'A test sneaker with stock-backed purchasing.',
        'status' => ProductStatus::Published,
        'regular_price' => 3000,
        'sale_price' => 2500,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
        'published_at' => now()->subHour(),
    ]);

    $product->categories()->attach($category);
    $product->attributeValues()->attach($black);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'MR-BLK-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 5,
        'reserved_quantity' => 1,
    ]);
    $variant->attributeValues()->attach($black);

    ProductImage::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'path' => 'https://example.test/images/merge-runner.jpg',
        'alt_text' => 'Merge Runner black profile',
        'is_primary' => true,
    ]);

    $draftProduct = Product::query()->create([
        'name' => 'Draft Cart Shoe',
        'slug' => 'draft-cart-shoe',
        'status' => ProductStatus::Draft,
        'regular_price' => 2000,
    ]);

    $draftVariant = ProductVariant::query()->create([
        'product_id' => $draftProduct->id,
        'sku' => 'DCS-41',
        'stock_quantity' => 5,
    ]);

    return compact('brand', 'category', 'black', 'product', 'variant', 'draftProduct', 'draftVariant');
}

test('guests can add items to cart and manage server-priced quantities', function () {
    $fixture = phaseEightCatalogFixture();

    $this->from(route('shop'))
        ->post(route('cart.items.store'), [
            'product_variant_id' => $fixture['variant']->id,
            'quantity' => 2,
        ])
        ->assertRedirect(route('shop'))
        ->assertSessionHas('status', 'Added to cart.');

    $cart = Cart::query()->with('items')->firstOrFail();
    $cartItem = $cart->items->first();

    expect($cart->user_id)->toBeNull()
        ->and($cart->status)->toBe(CartStatus::Active)
        ->and($cartItem)->toBeInstanceOf(CartItem::class)
        ->and($cartItem->quantity)->toBe(2)
        ->and((float) $cartItem->unit_price_snapshot)->toBe(2500.0)
        ->and($cartItem->options['product_name'])->toBe('Merge Runner');

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Shopping cart')
        ->assertSee('Merge Runner')
        ->assertSee('BDT 5,000.00');

    Livewire::test(CartPage::class)
        ->call('increment', $cartItem->id)
        ->assertHasNoErrors()
        ->assertSee('BDT 7,500.00')
        ->call('updateQuantity', $cartItem->id, 4)
        ->assertHasNoErrors()
        ->assertSee('BDT 10,000.00')
        ->call('remove', $cartItem->id)
        ->assertSee('Your cart is empty');
});

test('cart rejects unpublished products and quantities above available stock', function () {
    $fixture = phaseEightCatalogFixture();

    $this->from(route('shop'))
        ->post(route('cart.items.store'), [
            'product_variant_id' => $fixture['draftVariant']->id,
            'quantity' => 1,
        ])
        ->assertRedirect(route('shop'))
        ->assertSessionHasErrors('product_variant_id');

    $this->from(route('shop'))
        ->post(route('cart.items.store'), [
            'product_variant_id' => $fixture['variant']->id,
            'quantity' => 5,
        ])
        ->assertRedirect(route('shop'))
        ->assertSessionHasErrors('quantity');
});

test('product detail actions add to cart and save to wishlist', function () {
    $fixture = phaseEightCatalogFixture();

    Livewire::test(ProductShow::class, ['product' => $fixture['product']])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors()
        ->assertSee('Added to cart.');

    expect(CartItem::query()->where('product_variant_id', $fixture['variant']->id)->first()?->quantity)->toBe(2);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ProductShow::class, ['product' => $fixture['product']])
        ->call('addToWishlist')
        ->assertHasNoErrors()
        ->assertSee('Saved to wishlist.');

    expect(WishlistItem::query()->where('product_id', $fixture['product']->id)->where('product_variant_id', $fixture['variant']->id)->exists())->toBeTrue();
});

test('authenticated customers can save remove and move wishlist items to cart', function () {
    $fixture = phaseEightCatalogFixture();
    $user = User::factory()->create();

    $this->get(route('wishlist'))
        ->assertRedirect(route('login'));

    $this->actingAs($user)
        ->from(route('products.show', ['product' => $fixture['product']->slug]))
        ->post(route('wishlist.items.store'), [
            'product_id' => $fixture['product']->id,
            'product_variant_id' => $fixture['variant']->id,
        ])
        ->assertRedirect(route('products.show', ['product' => $fixture['product']->slug]))
        ->assertSessionHas('status', 'Saved to wishlist.');

    $wishlistItem = WishlistItem::query()->where('product_id', $fixture['product']->id)->firstOrFail();

    Livewire::actingAs($user)
        ->test(WishlistPage::class)
        ->assertSee('Merge Runner')
        ->call('moveToCart', $wishlistItem->id)
        ->assertHasNoErrors()
        ->assertSee('Your wishlist is empty');

    expect(WishlistItem::query()->whereKey($wishlistItem->id)->exists())->toBeFalse()
        ->and(CartItem::query()->where('product_variant_id', $fixture['variant']->id)->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->exists())->toBeTrue();

    $this->actingAs($user)
        ->post(route('wishlist.items.store'), [
            'product_id' => $fixture['product']->id,
            'product_variant_id' => $fixture['variant']->id,
        ])
        ->assertSessionHas('status', 'Saved to wishlist.');

    $wishlistItem = WishlistItem::query()->where('product_id', $fixture['product']->id)->firstOrFail();

    Livewire::actingAs($user)
        ->test(WishlistPage::class)
        ->call('remove', $wishlistItem->id)
        ->assertSee('Your wishlist is empty');
});

test('guest cart merges into customer cart on login', function () {
    $fixture = phaseEightCatalogFixture();
    $user = User::factory()->create();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 2,
    ])->assertSessionHas('status', 'Added to cart.');

    $guestCart = Cart::query()->whereNull('user_id')->firstOrFail();
    $userCart = Cart::query()->create([
        'user_id' => $user->id,
        'status' => CartStatus::Active,
        'currency_code' => 'BDT',
    ]);

    CartItem::query()->create([
        'cart_id' => $userCart->id,
        'product_id' => $fixture['product']->id,
        'product_variant_id' => $fixture['variant']->id,
        'quantity' => 1,
        'unit_price_snapshot' => 2500,
    ]);

    event(new Login('web', $user, false));

    expect($guestCart->refresh()->status)->toBe(CartStatus::Merged)
        ->and($userCart->refresh()->items()->where('product_variant_id', $fixture['variant']->id)->first()?->quantity)->toBe(3);
});
