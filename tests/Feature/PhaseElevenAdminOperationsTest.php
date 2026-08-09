<?php

use App\Enums\ContentStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Marketing\MarketingIndex;
use App\Livewire\Admin\Operations\CustomerIndex;
use App\Livewire\Admin\Operations\OrderIndex;
use App\Livewire\Admin\Settings\StoreSettingsIndex;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function phaseElevenAdmin(string $role = AdminPermissions::Admin): User
{
    $admin = User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
    ]);

    $admin->assignRole($role);

    return $admin;
}

function phaseElevenOrderFixture(): array
{
    $customer = User::factory()->create([
        'name' => 'Nadia Customer',
        'email' => 'nadia.phase11@example.test',
    ]);

    $product = Product::query()->create([
        'name' => 'Phase Eleven Runner',
        'slug' => 'phase-eleven-runner',
        'base_sku' => 'P11-RUN',
        'status' => ProductStatus::Published,
        'regular_price' => 4200,
        'published_at' => now()->subHour(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'P11-RUN-41',
        'option_label' => 'Black / EU 41',
        'stock_quantity' => 8,
    ]);

    $shippingZone = ShippingZone::query()->create([
        'name' => 'Bangladesh',
        'countries' => ['BD'],
        'regions' => ['Dhaka'],
    ]);

    $shippingMethod = ShippingMethod::query()->create([
        'shipping_zone_id' => $shippingZone->id,
        'name' => 'Dhaka standard',
        'code' => 'dhaka-standard',
        'price' => 100,
        'estimated_days_min' => 2,
        'estimated_days_max' => 4,
    ]);

    $order = Order::query()->create([
        'user_id' => $customer->id,
        'shipping_method_id' => $shippingMethod->id,
        'order_number' => 'AV-PHASE-11',
        'customer_name' => $customer->name,
        'email' => $customer->email,
        'phone' => '+8801700000000',
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Pending,
        'currency_code' => 'BDT',
        'subtotal' => 4200,
        'discount_total' => 0,
        'tax_total' => 0,
        'shipping_total' => 100,
        'grand_total' => 4300,
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
        'unit_price' => 4200,
        'line_total' => 4200,
    ]);

    Payment::query()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CashOnDelivery,
        'status' => PaymentStatus::Pending,
        'amount' => 4300,
        'provider' => 'offline',
    ]);

    return compact('customer', 'product', 'variant', 'shippingZone', 'shippingMethod', 'order');
}

test('admin operation routes render and order controls write audit history', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseElevenAdmin();
    $fixture = phaseElevenOrderFixture();
    $order = $fixture['order'];

    $this->actingAs($admin)
        ->get(route('admin.operations.orders'))
        ->assertOk()
        ->assertSee('Orders')
        ->assertSee('AV-PHASE-11');

    Livewire::actingAs($admin)
        ->test(OrderIndex::class)
        ->call('selectOrder', $order->id)
        ->set('form.status', OrderStatus::Processing->value)
        ->set('form.note', 'Packed at warehouse')
        ->call('updateStatus')
        ->assertHasNoErrors()
        ->set('form.payment_status', PaymentStatus::Paid->value)
        ->set('form.note', 'Manual payment confirmed')
        ->call('updatePayment')
        ->assertHasNoErrors()
        ->set('noteBody', 'Customer asked for morning delivery.')
        ->set('noteVisibleToCustomer', true)
        ->call('addNote')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Processing)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and(OrderStatusEvent::query()->where('order_id', $order->id)->first()?->note)->toBe('Packed at warehouse')
        ->and(PaymentEvent::query()->whereHas('payment', fn ($query) => $query->where('order_id', $order->id))->first()?->note)->toBe('Manual payment confirmed')
        ->and(OrderNote::query()->where('order_id', $order->id)->first()?->is_customer_visible)->toBeTrue();
});

test('admin customer workspace renders customer records and updates profiles', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseElevenAdmin();
    $fixture = phaseElevenOrderFixture();
    $customer = $fixture['customer'];

    $this->actingAs($admin)
        ->get(route('admin.operations.customers'))
        ->assertOk()
        ->assertSee('Customers')
        ->assertSee('Nadia Customer');

    Livewire::actingAs($admin)
        ->test(CustomerIndex::class)
        ->call('selectCustomer', $customer->id)
        ->set('form.name', 'Nadia Support')
        ->set('form.email', 'nadia.support@example.test')
        ->call('updateCustomer')
        ->assertHasNoErrors();

    expect($customer->refresh()->name)->toBe('Nadia Support')
        ->and($customer->email)->toBe('nadia.support@example.test');
});

test('admin marketing workspace manages campaigns coupons subscribers and featured products', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseElevenAdmin();

    $product = Product::query()->create([
        'name' => 'Featured Phase Runner',
        'slug' => 'featured-phase-runner',
        'base_sku' => 'P11-FEATURED',
        'status' => ProductStatus::Published,
        'regular_price' => 3900,
        'published_at' => now()->subHour(),
    ]);

    $subscriber = NewsletterSubscriber::query()->create([
        'email' => 'subscriber@example.test',
        'name' => 'Subscriber One',
        'status' => 'subscribed',
        'subscribed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.marketing'))
        ->assertOk()
        ->assertSee('Campaigns and coupons');

    $component = Livewire::actingAs($admin)
        ->test(MarketingIndex::class)
        ->set('campaignForm.name', 'Holiday Launch')
        ->set('campaignForm.status', ContentStatus::Published->value)
        ->call('saveCampaign')
        ->assertHasNoErrors();

    $campaign = Campaign::query()->firstOrFail();

    $component
        ->set('couponForm.campaign_id', (string) $campaign->id)
        ->set('couponForm.code', 'phase250')
        ->set('couponForm.name', 'Phase 250')
        ->set('couponForm.type', CouponType::Cart->value)
        ->set('couponForm.discount_type', DiscountType::Fixed->value)
        ->set('couponForm.value', '250')
        ->set('couponForm.is_active', true)
        ->call('saveCoupon')
        ->assertHasNoErrors()
        ->call('updateSubscriberStatus', $subscriber->id, 'unsubscribed')
        ->assertHasNoErrors()
        ->call('toggleFeaturedProduct', $product->id)
        ->assertHasNoErrors();

    expect($campaign->refresh()->slug)->toBe('holiday-launch')
        ->and(Coupon::query()->first()?->code)->toBe('PHASE250')
        ->and(Coupon::query()->first()?->campaign_id)->toBe($campaign->id)
        ->and($subscriber->refresh()->status)->toBe('unsubscribed')
        ->and($subscriber->unsubscribed_at)->not->toBeNull()
        ->and($product->refresh()->is_featured)->toBeTrue();
});

test('store settings persist public configuration and shipping rates', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseElevenAdmin(AdminPermissions::SuperAdmin);

    $this->actingAs($admin)
        ->get(route('admin.settings.store'))
        ->assertOk()
        ->assertSee('Store settings')
        ->assertSee('Shipping');

    Livewire::actingAs($admin)
        ->test(StoreSettingsIndex::class)
        ->set('settingsForm.brand__name', 'Amarvero Studio')
        ->set('settingsForm.contact__email', 'support@example.test')
        ->set('settingsForm.orders__return_window_days', 14)
        ->set('settingsForm.payments__cash_on_delivery_enabled', false)
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->set('zoneForm.name', 'Bangladesh Metro')
        ->set('zoneForm.countries', 'BD')
        ->set('zoneForm.regions', 'Dhaka, Chattogram')
        ->set('zoneForm.sort_order', 1)
        ->call('saveZone')
        ->assertHasNoErrors();

    $zone = ShippingZone::query()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(StoreSettingsIndex::class)
        ->set('methodForm.shipping_zone_id', $zone->id)
        ->set('methodForm.name', 'Metro standard')
        ->set('methodForm.code', 'metro-standard')
        ->set('methodForm.price', '90')
        ->set('methodForm.free_shipping_threshold', '2500')
        ->set('methodForm.estimated_days_min', 1)
        ->set('methodForm.estimated_days_max', 3)
        ->set('methodForm.sort_order', 1)
        ->call('saveMethod')
        ->assertHasNoErrors();

    expect(SiteSetting::query()->where('key', 'brand.name')->first()?->value)->toBe(['value' => 'Amarvero Studio'])
        ->and(SiteSetting::query()->where('key', 'contact.email')->first()?->value)->toBe(['value' => 'support@example.test'])
        ->and(SiteSetting::query()->where('key', 'orders.return_window_days')->first()?->value)->toBe(['value' => 14])
        ->and(SiteSetting::query()->where('key', 'payments.cash_on_delivery_enabled')->first()?->value)->toBe(['value' => false])
        ->and($zone->refresh()->countries)->toBe(['BD'])
        ->and($zone->regions)->toBe(['Dhaka', 'Chattogram'])
        ->and(ShippingMethod::query()->first()?->code)->toBe('metro-standard')
        ->and((float) ShippingMethod::query()->first()?->price)->toBe(90.0);
});
