<?php

use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RefundStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use App\Support\AdminPermissions;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;

function phaseFourteenAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    return $admin;
}

/**
 * @return array{product: Product, variant: ProductVariant, coupon: Coupon, returningCustomer: User, newCustomer: User}
 */
function phaseFourteenDashboardFixture(): array
{
    $brand = Brand::factory()->create([
        'name' => 'Metric Brand',
        'slug' => 'metric-brand',
    ]);
    $category = Category::factory()->create([
        'name' => 'Metric Category',
        'slug' => 'metric-category',
    ]);
    $product = Product::factory()
        ->for($brand)
        ->create([
            'name' => 'Metric Runner',
            'slug' => 'metric-runner',
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(10),
        ]);
    $product->categories()->attach($category);

    $variant = ProductVariant::factory()
        ->for($product)
        ->create([
            'sku' => 'METRIC-RUNNER-41',
            'stock_quantity' => 3,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

    ProductVariant::factory()
        ->for($product)
        ->create([
            'sku' => 'METRIC-RUNNER-42',
            'stock_quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

    $returningCustomer = User::factory()->create([
        'name' => 'Returning Metric Customer',
        'created_at' => now()->subDays(70),
        'updated_at' => now()->subDays(70),
    ]);
    $newCustomer = User::factory()->create([
        'name' => 'New Metric Customer',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $coupon = Coupon::query()->create([
        'code' => 'METRIC500',
        'name' => 'Metric 500',
        'type' => CouponType::Cart,
        'discount_type' => DiscountType::Fixed,
        'value' => 500,
        'is_active' => true,
    ]);

    phaseFourteenOrder(
        user: $returningCustomer,
        product: $product,
        variant: $variant,
        placedAt: CarbonImmutable::now()->subDays(40),
        status: OrderStatus::Delivered,
        paymentStatus: PaymentStatus::Paid,
        grandTotal: 4000,
        quantity: 1,
        lineTotal: 4000,
    );

    $currentPaidOrder = phaseFourteenOrder(
        user: $returningCustomer,
        product: $product,
        variant: $variant,
        placedAt: CarbonImmutable::now()->subDays(2),
        status: OrderStatus::Delivered,
        paymentStatus: PaymentStatus::Paid,
        grandTotal: 10000,
        quantity: 3,
        lineTotal: 9000,
        coupon: $coupon,
    );

    phaseFourteenOrder(
        user: $newCustomer,
        product: $product,
        variant: $variant,
        placedAt: CarbonImmutable::now()->subDay(),
        status: OrderStatus::Processing,
        paymentStatus: PaymentStatus::Pending,
        grandTotal: 2000,
        quantity: 1,
        lineTotal: 2000,
    );

    Refund::query()->create([
        'order_id' => $currentPaidOrder->id,
        'status' => RefundStatus::Refunded,
        'amount' => 1000,
        'transaction_id' => 'METRIC-REFUND-1',
        'reason' => 'Dashboard metric fixture refund.',
        'refunded_at' => now()->subDay(),
    ]);

    return compact('product', 'variant', 'coupon', 'returningCustomer', 'newCustomer');
}

function phaseFourteenOrder(
    User $user,
    Product $product,
    ProductVariant $variant,
    CarbonImmutable $placedAt,
    OrderStatus $status,
    PaymentStatus $paymentStatus,
    float $grandTotal,
    int $quantity,
    float $lineTotal,
    ?Coupon $coupon = null,
): Order {
    $order = Order::factory()
        ->for($user)
        ->create([
            'coupon_id' => $coupon?->id,
            'order_number' => 'METRIC-'.$placedAt->format('md').'-'.fake()->unique()->numberBetween(100, 999),
            'customer_name' => $user->name,
            'email' => $user->email,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => $grandTotal,
            'discount_total' => $coupon instanceof Coupon ? 500 : 0,
            'shipping_total' => 0,
            'grand_total' => $grandTotal,
            'placed_at' => $placedAt,
        ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_name' => $variant->option_label,
        'sku' => $variant->sku,
        'quantity' => $quantity,
        'unit_price' => $quantity === 0 ? 0 : $lineTotal / $quantity,
        'discount_total' => $coupon instanceof Coupon ? 500 : 0,
        'tax_total' => 0,
        'line_total' => $lineTotal,
    ]);

    if ($coupon instanceof Coupon) {
        CouponRedemption::query()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_amount' => 500,
            'redeemed_at' => $placedAt,
        ]);
    }

    return $order;
}

test('admin dashboard renders period comparisons sales leaders coupon usage and stock risks', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseFourteenAdmin();
    phaseFourteenDashboardFixture();

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['range' => '30d']))
        ->assertOk()
        ->assertSee('Date range')
        ->assertSee('Last 30 days')
        ->assertSee('Gross revenue')
        ->assertSee('BDT 10,000.00')
        ->assertSee('Net sales')
        ->assertSee('BDT 9,000.00')
        ->assertSee('Average order value')
        ->assertSee('BDT 6,000.00')
        ->assertSee('Returning customers')
        ->assertSee('Order pipeline')
        ->assertSee('Delivered')
        ->assertSee('Processing')
        ->assertSee('Best-selling products')
        ->assertSee('Metric Runner')
        ->assertSee('METRIC-RUNNER-41')
        ->assertSee('Metric Category')
        ->assertSee('Metric Brand')
        ->assertSee('Coupon usage')
        ->assertSee('METRIC500')
        ->assertSee('Inventory watchlist')
        ->assertSee('Low stock')
        ->assertSee('Out of stock');
});

test('admin dashboard validates custom date filters', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseFourteenAdmin();

    $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->get(route('admin.dashboard', ['range' => 'custom']))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasErrors(['start_date', 'end_date']);
});
