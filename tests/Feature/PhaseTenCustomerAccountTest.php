<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Account\AddressBook;
use App\Livewire\Account\ReviewManager;
use App\Models\Brand;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

function phaseTenAccountFixture(): array
{
    $user = User::factory()->create([
        'name' => 'Nadia Customer',
        'email' => 'nadia.account@example.test',
    ]);
    $otherUser = User::factory()->create();

    $brand = Brand::query()->create([
        'name' => 'Account Studio',
        'slug' => 'account-studio',
    ]);

    $product = Product::query()->create([
        'brand_id' => $brand->id,
        'name' => 'Account Runner',
        'slug' => 'account-runner',
        'status' => ProductStatus::Published,
        'regular_price' => 3200,
        'published_at' => now()->subHour(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'AR-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 4,
    ]);

    $order = phaseTenOrder($user, $product, $variant, 'AV-ACCOUNT-1');
    $otherOrder = phaseTenOrder($otherUser, $product, $variant, 'AV-ACCOUNT-2');

    return compact('user', 'otherUser', 'brand', 'product', 'variant', 'order', 'otherOrder');
}

function phaseTenOrder(User $user, Product $product, ProductVariant $variant, string $orderNumber): Order
{
    $order = Order::query()->create([
        'user_id' => $user->id,
        'order_number' => $orderNumber,
        'customer_name' => $user->name,
        'email' => $user->email,
        'phone' => '+8801700000000',
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Pending,
        'currency_code' => 'BDT',
        'subtotal' => 3200,
        'discount_total' => 0,
        'tax_total' => 0,
        'shipping_total' => 100,
        'grand_total' => 3300,
        'placed_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_name' => $variant->option_label,
        'sku' => $variant->sku,
        'quantity' => 1,
        'unit_price' => 3200,
        'line_total' => 3200,
    ]);

    OrderAddress::query()->create([
        'order_id' => $order->id,
        'type' => AddressType::Shipping,
        'name' => $user->name,
        'phone' => '+8801700000000',
        'line_one' => 'House 12',
        'city' => 'Dhaka',
        'country_code' => 'BD',
    ]);

    Payment::query()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CashOnDelivery,
        'status' => PaymentStatus::Pending,
        'amount' => 3300,
        'provider' => 'offline',
    ]);

    return $order;
}

test('customer account routes render owned orders and protect other orders', function () {
    $fixture = phaseTenAccountFixture();

    $this->get(route('account.orders'))
        ->assertRedirect(route('login'));

    $this->actingAs($fixture['user'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Account overview')
        ->assertSee('Recent orders')
        ->assertSee('AV-ACCOUNT-1');

    $this->actingAs($fixture['user'])
        ->get(route('account.orders'))
        ->assertOk()
        ->assertSee('Order history')
        ->assertSee('AV-ACCOUNT-1')
        ->assertDontSee('AV-ACCOUNT-2');

    $this->actingAs($fixture['user'])
        ->get(route('account.orders.show', ['order' => $fixture['order']->order_number]))
        ->assertOk()
        ->assertSee('Account Runner')
        ->assertSee('BDT 3,300.00');

    $this->actingAs($fixture['user'])
        ->get(route('account.orders.show', ['order' => $fixture['otherOrder']->order_number]))
        ->assertNotFound();
});

test('customers can manage addresses and default destinations', function () {
    $fixture = phaseTenAccountFixture();

    Livewire::actingAs($fixture['user'])
        ->test(AddressBook::class)
        ->set('form.name', 'Nadia Customer')
        ->set('form.phone', '+8801700000000')
        ->set('form.line_one', 'House 12')
        ->set('form.area', 'Banani')
        ->set('form.city', 'Dhaka')
        ->set('form.region', 'Dhaka')
        ->set('form.is_default_shipping', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Address saved.');

    $firstAddress = CustomerAddress::query()->where('user_id', $fixture['user']->id)->firstOrFail();

    expect($firstAddress->is_default_shipping)->toBeTrue()
        ->and($firstAddress->country_code)->toBe('BD');

    Livewire::actingAs($fixture['user'])
        ->test(AddressBook::class)
        ->set('form.name', 'Office Desk')
        ->set('form.phone', '+8801800000000')
        ->set('form.line_one', 'Office Tower')
        ->set('form.city', 'Dhaka')
        ->set('form.is_default_shipping', true)
        ->set('form.is_default_billing', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($firstAddress->refresh()->is_default_shipping)->toBeFalse()
        ->and(CustomerAddress::query()->where('user_id', $fixture['user']->id)->where('is_default_shipping', true)->count())->toBe(1)
        ->and(CustomerAddress::query()->where('user_id', $fixture['user']->id)->where('is_default_billing', true)->count())->toBe(1);

    $secondAddress = CustomerAddress::query()->where('name', 'Office Desk')->firstOrFail();

    Livewire::actingAs($fixture['user'])
        ->test(AddressBook::class)
        ->call('edit', $secondAddress->id)
        ->set('form.city', 'Chattogram')
        ->call('save')
        ->assertHasNoErrors()
        ->call('delete', $secondAddress->id)
        ->assertSee('Address removed.');

    expect(CustomerAddress::query()->whereKey($secondAddress->id)->exists())->toBeFalse();
});

test('customers can submit edit and delete verified purchase reviews', function () {
    $fixture = phaseTenAccountFixture();
    $orderItem = OrderItem::query()->where('order_id', $fixture['order']->id)->firstOrFail();

    Livewire::actingAs($fixture['user'])
        ->test(ReviewManager::class)
        ->assertSee('Account Runner')
        ->call('startFromOrderItem', $orderItem->id)
        ->set('form.rating', 4)
        ->set('form.title', 'Comfortable pair')
        ->set('form.body', 'Comfortable for long walks around the city.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Review submitted for moderation.');

    $review = ProductReview::query()->where('user_id', $fixture['user']->id)->firstOrFail();

    expect($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->is_verified_purchase)->toBeTrue()
        ->and($review->rating)->toBe(4)
        ->and($review->order_id)->toBe($fixture['order']->id);

    Livewire::actingAs($fixture['user'])
        ->test(ReviewManager::class)
        ->call('edit', $review->id)
        ->set('form.body', 'Still comfortable after a longer commute.')
        ->call('save')
        ->assertHasNoErrors()
        ->call('delete', $review->id)
        ->assertSee('Review removed.');

    expect(ProductReview::query()->whereKey($review->id)->exists())->toBeFalse();
});

test('reviews cannot be submitted for another customer order', function () {
    $fixture = phaseTenAccountFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ReviewManager::class)
        ->set('form.product_id', $fixture['product']->id)
        ->set('form.product_variant_id', $fixture['variant']->id)
        ->set('form.order_id', $fixture['otherOrder']->id)
        ->set('form.rating', 5)
        ->set('form.body', 'Trying to review another customer order.')
        ->call('save')
        ->assertHasErrors(['reviews']);

    expect(ProductReview::query()->where('user_id', $fixture['user']->id)->count())->toBe(0);
});
