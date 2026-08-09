<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\CheckoutPage;
use App\Mail\Orders\OrderConfirmationMail;
use App\Mail\Orders\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\User;
use App\Support\Admin\AdminOrderManager;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function phaseSeventeenShippingFixture(): ShippingMethod
{
    $shippingZone = ShippingZone::query()->create([
        'name' => 'Phase seventeen zone',
        'countries' => ['BD'],
        'regions' => null,
        'is_active' => true,
    ]);

    return ShippingMethod::query()->create([
        'shipping_zone_id' => $shippingZone->id,
        'name' => 'Phase seventeen standard',
        'code' => 'phase-seventeen-standard',
        'price' => 120,
        'is_active' => true,
    ]);
}

function phaseSeventeenProductFixture(): array
{
    $product = Product::factory()->create([
        'name' => 'Mail Runner',
        'slug' => 'mail-runner',
        'status' => ProductStatus::Published,
        'regular_price' => 3200,
        'sale_price' => null,
        'published_at' => now()->subHour(),
    ]);

    $variant = ProductVariant::factory()
        ->for($product)
        ->create([
            'sku' => 'MAIL-RUN-41',
            'option_label' => 'Black / EU 41',
            'stock_quantity' => 8,
            'reserved_quantity' => 0,
        ]);

    return compact('product', 'variant');
}

function phaseSeventeenOrderFixture(): array
{
    $customer = User::factory()->create([
        'name' => 'Nadia Mail',
        'email' => 'nadia.mail@example.test',
    ]);
    $shippingMethod = phaseSeventeenShippingFixture();
    $catalog = phaseSeventeenProductFixture();

    $order = Order::query()->create([
        'user_id' => $customer->id,
        'shipping_method_id' => $shippingMethod->id,
        'order_number' => 'AV-PHASE-17',
        'customer_name' => $customer->name,
        'email' => $customer->email,
        'phone' => '+8801700000000',
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Pending,
        'currency_code' => 'BDT',
        'subtotal' => 3200,
        'discount_total' => 0,
        'tax_total' => 0,
        'shipping_total' => 120,
        'grand_total' => 3320,
        'placed_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $catalog['product']->id,
        'product_variant_id' => $catalog['variant']->id,
        'product_name' => 'Mail Runner',
        'variant_name' => 'Black / EU 41',
        'sku' => 'MAIL-RUN-41',
        'quantity' => 1,
        'unit_price' => 3200,
        'line_total' => 3200,
    ]);

    Payment::query()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CashOnDelivery,
        'status' => PaymentStatus::Pending,
        'amount' => 3320,
        'provider' => 'offline',
    ]);

    return compact('customer', 'shippingMethod', 'order') + $catalog;
}

test('checkout queues an order confirmation email after a successful order', function () {
    Mail::fake();

    $shippingMethod = phaseSeventeenShippingFixture();
    $catalog = phaseSeventeenProductFixture();

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $catalog['variant']->id,
        'quantity' => 1,
    ])->assertSessionHas('status', 'Added to cart.');

    Livewire::test(CheckoutPage::class)
        ->set('form.customer_name', 'Nadia Checkout')
        ->set('form.email', 'nadia.checkout@example.test')
        ->set('form.phone', '+8801700000000')
        ->set('form.line_one', 'Mail Street 12')
        ->set('form.city', 'Dhaka')
        ->set('shippingMethodId', $shippingMethod->id)
        ->set('paymentMethod', PaymentMethod::CashOnDelivery->value)
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->firstOrFail();

    Mail::assertQueued(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('nadia.checkout@example.test')
        && $mail->order->is($order));
});

test('admin status changes queue customer status emails only when the status moves', function () {
    Mail::fake();

    $fixture = phaseSeventeenOrderFixture();
    $admin = User::factory()->create();
    $order = $fixture['order'];

    app(AdminOrderManager::class)->updateOrderStatus($order, OrderStatus::Processing, $admin, 'Packed at warehouse');

    Mail::assertQueued(OrderStatusUpdatedMail::class, fn (OrderStatusUpdatedMail $mail): bool => $mail->hasTo($order->email)
        && $mail->order->is($order)
        && $mail->fromStatus === OrderStatus::Confirmed
        && $mail->toStatus === OrderStatus::Processing);

    Mail::fake();

    app(AdminOrderManager::class)->updateOrderStatus($order->refresh(), OrderStatus::Processing, $admin, 'Internal follow-up note');

    Mail::assertNotQueued(OrderStatusUpdatedMail::class);
});

test('order emails render customer safe order snapshots', function () {
    $fixture = phaseSeventeenOrderFixture();
    $order = $fixture['order']->load(['items', 'payments', 'shippingMethod']);

    expect((new OrderConfirmationMail($order))->render())
        ->toContain('Order received')
        ->toContain('AV-PHASE-17')
        ->toContain('Mail Runner')
        ->toContain('BDT 3,320.00');

    expect((new OrderStatusUpdatedMail($order, OrderStatus::Confirmed, OrderStatus::Shipped))->render())
        ->toContain('Order status updated')
        ->toContain('Confirmed')
        ->toContain('Shipped')
        ->toContain('Phase seventeen standard');
});
