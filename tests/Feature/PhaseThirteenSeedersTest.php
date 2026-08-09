<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Models\AnnouncementBar;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CustomerAddress;
use App\Models\Faq;
use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\InventoryMovement;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\PromotionalBanner;
use App\Models\Refund;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\ServiceBenefit;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\StaticPage;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Support\AdminPermissions;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('phase thirteen factories create core commerce records', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()
        ->for($brand)
        ->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subHour(),
        ]);
    $variant = ProductVariant::factory()
        ->for($product)
        ->create([
            'stock_quantity' => 8,
            'reserved_quantity' => 2,
        ]);
    $order = Order::factory()->create();

    $product->categories()->attach($category);

    expect($brand->is_active)->toBeTrue()
        ->and($category->is_active)->toBeTrue()
        ->and($product->status)->toBe(ProductStatus::Published)
        ->and($product->brand->is($brand))->toBeTrue()
        ->and($product->categories()->whereKey($category->id)->exists())->toBeTrue()
        ->and($variant->product->is($product))->toBeTrue()
        ->and($variant->availableQuantity())->toBe(6)
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(PaymentStatus::Pending);
});

test('database seeder creates an idempotent admin user and complete demo commerce dataset', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
    $customer = User::query()->where('email', 'customer@example.test')->firstOrFail();
    $coupon = Coupon::query()->where('code', 'WELCOME500')->firstOrFail();
    $order = Order::query()
        ->where('order_number', 'AV-DEMO-1001')
        ->with(['items', 'addresses', 'payments', 'shipments', 'returnRequests', 'refunds'])
        ->firstOrFail();

    expect(Role::count())->toBe(6)
        ->and(Permission::where('guard_name', 'web')->count())->toBeGreaterThan(40)
        ->and($admin->hasRole(AdminPermissions::SuperAdmin))->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($customer->name)->toBe('Nadia Demo Customer')
        ->and(Brand::count())->toBe(3)
        ->and(Category::count())->toBe(6)
        ->and(ProductCollection::count())->toBe(3)
        ->and(ProductAttribute::count())->toBe(3)
        ->and(AttributeValue::count())->toBe(10)
        ->and(Product::published()->count())->toBe(4)
        ->and(ProductVariant::count())->toBe(9)
        ->and(ProductImage::count())->toBe(4)
        ->and(InventoryMovement::where('reference_type', 'demo-seeder')->count())->toBe(9)
        ->and(ShippingZone::count())->toBe(1)
        ->and(ShippingMethod::count())->toBe(2)
        ->and(Campaign::where('slug', 'demo-launch')->exists())->toBeTrue()
        ->and($coupon->brands()->count())->toBe(2)
        ->and($coupon->categories()->count())->toBe(1)
        ->and($coupon->products()->count())->toBe(1)
        ->and(NewsletterSubscriber::where('email', 'newsletter@example.test')->exists())->toBeTrue()
        ->and(AnnouncementBar::count())->toBe(1)
        ->and(NavigationMenu::where('slug', 'primary')->exists())->toBeTrue()
        ->and(NavigationMenuItem::count())->toBe(6)
        ->and(HeroSlide::count())->toBe(3)
        ->and(HomepageSection::count())->toBe(6)
        ->and(PromotionalBanner::count())->toBe(3)
        ->and(StaticPage::count())->toBe(8)
        ->and(Faq::count())->toBe(6)
        ->and(ServiceBenefit::count())->toBe(6)
        ->and(Testimonial::count())->toBe(4)
        ->and(StoreLocation::count())->toBe(2)
        ->and(SocialLink::count())->toBe(4)
        ->and(FooterSection::count())->toBe(4)
        ->and(FooterLink::count())->toBe(16)
        ->and(SiteSetting::count())->toBe(19)
        ->and(CustomerAddress::count())->toBe(1)
        ->and(Wishlist::count())->toBe(1)
        ->and(WishlistItem::count())->toBe(1)
        ->and(Cart::count())->toBe(1)
        ->and($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and((float) $order->grand_total)->toBe(4820.0)
        ->and($order->items)->toHaveCount(1)
        ->and($order->addresses)->toHaveCount(2)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->shipments)->toHaveCount(1)
        ->and($order->returnRequests)->toHaveCount(1)
        ->and($order->refunds)->toHaveCount(1)
        ->and(OrderItem::where('sku', 'AV-CRL-BLK-41')->exists())->toBeTrue()
        ->and(OrderStatusEvent::count())->toBe(1)
        ->and(Payment::count())->toBe(1)
        ->and(PaymentEvent::count())->toBe(1)
        ->and(OrderNote::count())->toBe(1)
        ->and(ReturnRequest::count())->toBe(1)
        ->and(ReturnItem::count())->toBe(1)
        ->and(Refund::count())->toBe(1)
        ->and(CouponRedemption::count())->toBe(1)
        ->and(ProductReview::where('status', ReviewStatus::Approved)->count())->toBe(1);
});

test('seeded storefront content is visible on public pages and sitemap', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Demo launch: free standard delivery over BDT 5,000.')
        ->assertSee('Footwear built for daily movement')
        ->assertSee('Daily rotation')
        ->assertSee('New this week')
        ->assertSee('Office ready')
        ->assertSee('Checkout confidence')
        ->assertSee('Welcome offer')
        ->assertSee('Daily pairs stocked')
        ->assertSee('Amarvero Studio Dhaka');

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('City Runner Low')
        ->assertSee('Metro Leather Loafer')
        ->assertSee('Studio Strap Sandal')
        ->assertSee('Junior Sprint Sneaker');

    $this->get(route('pages.show', ['page' => 'about']))
        ->assertOk()
        ->assertSee('About Amarvero')
        ->assertSee('original demo footwear storefront');

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('products.show', ['product' => 'city-runner']), false)
        ->assertSee(route('categories.show', ['slug' => 'sneakers']), false)
        ->assertSee(route('pages.show', ['page' => 'shipping-policy']), false);
});
