<?php

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\CartStatus;
use App\Enums\ContentStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReviewStatus;
use App\Enums\ShipmentStatus;
use App\Models\AnnouncementBar;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
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
use App\Models\OrderAddress;
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
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SizeGuide;
use App\Models\SocialLink;
use App\Models\StaticPage;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Support\Settings\SettingsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EcommerceDemoSeeder extends Seeder
{
    private const string PlaceholderImage = '/images/storefront/hero-footwear.png';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSettings();
            $admin = User::query()->where('email', $this->adminEmail())->first();

            $catalog = $this->seedCatalog($admin);
            $this->seedMarketing($catalog);
            $this->seedContent($catalog);
            $this->seedCustomersAndOrders($catalog, $admin);
        });
    }

    protected function adminEmail(): string
    {
        $email = config('admin.seed.email');
        $email = is_scalar($email) ? trim((string) $email) : '';

        return $email !== '' ? $email : 'admin@example.test';
    }

    protected function seedSettings(): void
    {
        app(SettingsManager::class)->saveMany([
            'brand.name' => 'Amarvero',
            'brand.logo_path' => '',
            'brand.dark_logo_path' => '',
            'brand.light_logo_path' => '',
            'brand.favicon_path' => '',
            'contact.email' => 'care@amarvero.test',
            'contact.support_phone' => '+8801700000000',
            'seo.default_title' => 'Amarvero premium footwear',
            'seo.default_description' => 'Original footwear commerce demo with dynamic catalog, secure checkout, account tools, and admin operations.',
            'seo.open_graph_image' => self::PlaceholderImage,
            'analytics.placeholder_id' => 'G-DEMOONLY',
            'maintenance.enabled' => false,
            'newsletter.enabled' => true,
            'invoice.from_name' => 'Amarvero Studio',
            'orders.return_window_days' => 7,
            'orders.cancellation_window_hours' => 12,
            'reviews.verified_purchase_only' => true,
            'payments.cash_on_delivery_enabled' => true,
            'payments.bank_transfer_instructions' => 'Use environment-managed bank details in production.',
        ]);
    }

    /**
     * @return array{
     *     brands: array<string, Brand>,
     *     categories: array<string, Category>,
     *     collections: array<string, ProductCollection>,
     *     attributes: array<string, ProductAttribute>,
     *     values: array<string, AttributeValue>,
     *     products: array<string, Product>,
     *     variants: array<string, ProductVariant>,
     *     shipping_methods: array<string, ShippingMethod>,
     *     customers: array<string, User>
     * }
     */
    protected function seedCatalog(?User $admin): array
    {
        $brands = [
            'urban-thread' => $this->brand('Urban Thread', 'urban-thread', 'Clean city footwear with durable everyday profiles.', true, 1),
            'northline-sole' => $this->brand('Northline Sole', 'northline-sole', 'Weather-ready soles and workday comfort.', true, 2),
            'vela-step' => $this->brand('Vela Step', 'vela-step', 'Lightweight casual silhouettes for weekends.', false, 3),
        ];

        $categories = [
            'men' => $this->category('Men', 'men', null, 1),
            'women' => $this->category('Women', 'women', null, 2),
            'kids' => $this->category('Kids', 'kids', null, 3),
        ];
        $categories['sneakers'] = $this->category('Sneakers', 'sneakers', $categories['men'], 4);
        $categories['loafers'] = $this->category('Loafers', 'loafers', $categories['men'], 5);
        $categories['sandals'] = $this->category('Sandals', 'sandals', $categories['women'], 6);

        $collections = [
            'daily-rotation' => $this->collection('Daily Rotation', 'daily-rotation', 'Pairs for repeat city wear.', true, 1),
            'office-ready' => $this->collection('Office Ready', 'office-ready', 'Structured shoes for work hours.', true, 2),
            'weekend-light' => $this->collection('Weekend Light', 'weekend-light', 'Relaxed picks for off-duty plans.', false, 3),
        ];

        $attributes = [
            'color' => $this->attribute('Color', 'color', 'color', true, true, 1),
            'size' => $this->attribute('Size', 'size', 'select', true, true, 2),
            'fit' => $this->attribute('Fit', 'fit', 'select', false, true, 3),
        ];

        $values = [
            'black' => $this->attributeValue($attributes['color'], 'Black', 'black', '#111827', 1),
            'white' => $this->attributeValue($attributes['color'], 'White', 'white', '#f8fafc', 2),
            'tan' => $this->attributeValue($attributes['color'], 'Tan', 'tan', '#b7794b', 3),
            'navy' => $this->attributeValue($attributes['color'], 'Navy', 'navy', '#1e3a8a', 4),
            'size-39' => $this->attributeValue($attributes['size'], 'EU 39', 'eu-39', null, 1),
            'size-40' => $this->attributeValue($attributes['size'], 'EU 40', 'eu-40', null, 2),
            'size-41' => $this->attributeValue($attributes['size'], 'EU 41', 'eu-41', null, 3),
            'size-42' => $this->attributeValue($attributes['size'], 'EU 42', 'eu-42', null, 4),
            'regular' => $this->attributeValue($attributes['fit'], 'Regular', 'regular', null, 1),
            'wide' => $this->attributeValue($attributes['fit'], 'Wide', 'wide', null, 2),
        ];

        $sizeGuide = SizeGuide::query()->updateOrCreate(
            ['slug' => 'amarvero-footwear-size-guide'],
            [
                'brand_id' => $brands['urban-thread']->id,
                'category_id' => $categories['sneakers']->id,
                'name' => 'Amarvero footwear size guide',
                'content' => 'Measure heel-to-toe length and compare against the nearest EU size before ordering.',
                'measurements' => [
                    ['label' => 'EU 39', 'measurement' => '24.5 cm'],
                    ['label' => 'EU 40', 'measurement' => '25.2 cm'],
                    ['label' => 'EU 41', 'measurement' => '26.0 cm'],
                    ['label' => 'EU 42', 'measurement' => '26.7 cm'],
                ],
                'is_active' => true,
            ],
        );

        $productBlueprints = [
            [
                'key' => 'city-runner',
                'name' => 'City Runner Low',
                'brand' => 'urban-thread',
                'categories' => ['men', 'sneakers'],
                'collections' => ['daily-rotation'],
                'gender' => 'men',
                'material' => 'Knit mesh',
                'regular_price' => 5200,
                'sale_price' => 4700,
                'flags' => ['featured' => true, 'new' => true, 'best' => false],
                'variants' => [
                    ['sku' => 'AV-CRL-BLK-40', 'color' => 'black', 'size' => 'size-40', 'stock' => 18],
                    ['sku' => 'AV-CRL-BLK-41', 'color' => 'black', 'size' => 'size-41', 'stock' => 16],
                    ['sku' => 'AV-CRL-WHT-42', 'color' => 'white', 'size' => 'size-42', 'stock' => 9],
                ],
            ],
            [
                'key' => 'metro-loafer',
                'name' => 'Metro Leather Loafer',
                'brand' => 'northline-sole',
                'categories' => ['men', 'loafers'],
                'collections' => ['office-ready'],
                'gender' => 'men',
                'material' => 'Leather',
                'regular_price' => 6400,
                'sale_price' => null,
                'flags' => ['featured' => false, 'new' => false, 'best' => true],
                'variants' => [
                    ['sku' => 'AV-MLL-TAN-41', 'color' => 'tan', 'size' => 'size-41', 'stock' => 7],
                    ['sku' => 'AV-MLL-BLK-42', 'color' => 'black', 'size' => 'size-42', 'stock' => 12],
                ],
            ],
            [
                'key' => 'studio-sandal',
                'name' => 'Studio Strap Sandal',
                'brand' => 'vela-step',
                'categories' => ['women', 'sandals'],
                'collections' => ['weekend-light'],
                'gender' => 'women',
                'material' => 'Synthetic',
                'regular_price' => 3600,
                'sale_price' => 3200,
                'flags' => ['featured' => true, 'new' => false, 'best' => true],
                'variants' => [
                    ['sku' => 'AV-SSS-TAN-39', 'color' => 'tan', 'size' => 'size-39', 'stock' => 13],
                    ['sku' => 'AV-SSS-NVY-40', 'color' => 'navy', 'size' => 'size-40', 'stock' => 5],
                ],
            ],
            [
                'key' => 'junior-sprint',
                'name' => 'Junior Sprint Sneaker',
                'brand' => 'urban-thread',
                'categories' => ['kids', 'sneakers'],
                'collections' => ['daily-rotation'],
                'gender' => 'kids',
                'material' => 'Canvas',
                'regular_price' => 2800,
                'sale_price' => null,
                'flags' => ['featured' => false, 'new' => true, 'best' => false],
                'variants' => [
                    ['sku' => 'AV-JSS-WHT-39', 'color' => 'white', 'size' => 'size-39', 'stock' => 20],
                    ['sku' => 'AV-JSS-NVY-40', 'color' => 'navy', 'size' => 'size-40', 'stock' => 14],
                ],
            ],
        ];

        $products = [];
        $variants = [];

        foreach ($productBlueprints as $index => $blueprint) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $blueprint['key']],
                [
                    'brand_id' => $brands[$blueprint['brand']]->id,
                    'name' => $blueprint['name'],
                    'base_sku' => 'AV-'.strtoupper(str_replace('-', '', $blueprint['key'])),
                    'short_description' => 'Original Amarvero demo footwear for '.$blueprint['gender'].' discovery.',
                    'description' => 'Built for a realistic e-commerce demo with variants, stock, merchandising flags, SEO copy, and checkout-ready pricing.',
                    'status' => ProductStatus::Published,
                    'gender' => $blueprint['gender'],
                    'material' => $blueprint['material'],
                    'care_instructions' => 'Wipe with a soft cloth and dry away from direct heat.',
                    'regular_price' => $blueprint['regular_price'],
                    'sale_price' => $blueprint['sale_price'],
                    'cost_price' => (int) $blueprint['regular_price'] * 0.52,
                    'sale_starts_at' => $blueprint['sale_price'] ? now()->subWeek() : null,
                    'sale_ends_at' => $blueprint['sale_price'] ? now()->addWeeks(3) : null,
                    'published_at' => now()->subDays(10 - $index),
                    'is_featured' => $blueprint['flags']['featured'],
                    'is_new_arrival' => $blueprint['flags']['new'],
                    'is_best_seller' => $blueprint['flags']['best'],
                    'track_inventory' => true,
                    'allow_backorder' => false,
                    'seo_title' => $blueprint['name'].' | Amarvero',
                    'seo_description' => 'Shop '.$blueprint['name'].' with Amarvero demo pricing, variants, and checkout-ready stock.',
                ],
            );

            $product->categories()->syncWithoutDetaching(array_map(fn (string $slug): int => $categories[$slug]->id, $blueprint['categories']));
            $product->collections()->syncWithoutDetaching(array_map(fn (string $slug): int => $collections[$slug]->id, $blueprint['collections']));
            $product->attributeValues()->syncWithoutDetaching([
                $values['regular']->id,
                ...array_map(fn (array $variant): int => $values[$variant['color']]->id, $blueprint['variants']),
                ...array_map(fn (array $variant): int => $values[$variant['size']]->id, $blueprint['variants']),
            ]);
            $product->sizeGuides()->syncWithoutDetaching([$sizeGuide->id]);

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'path' => self::PlaceholderImage],
                [
                    'disk' => 'public',
                    'alt_text' => $product->name.' studio footwear image',
                    'is_primary' => true,
                    'sort_order' => 0,
                ],
            );

            $products[$blueprint['key']] = $product;

            foreach ($blueprint['variants'] as $variantIndex => $variantBlueprint) {
                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => $variantBlueprint['sku']],
                    [
                        'product_id' => $product->id,
                        'option_label' => $values[$variantBlueprint['color']]->value.' / '.$values[$variantBlueprint['size']]->value,
                        'stock_quantity' => $variantBlueprint['stock'],
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 4,
                        'allow_backorder' => false,
                        'is_active' => true,
                        'sort_order' => $variantIndex,
                        'weight_grams' => 850,
                        'dimensions' => ['length_cm' => 32, 'width_cm' => 20, 'height_cm' => 12],
                    ],
                );

                $variant->attributeValues()->syncWithoutDetaching([
                    $values[$variantBlueprint['color']]->id,
                    $values[$variantBlueprint['size']]->id,
                ]);

                if ($admin instanceof User) {
                    InventoryMovement::query()->firstOrCreate(
                        [
                            'product_variant_id' => $variant->id,
                            'reference_type' => 'demo-seeder',
                            'reference_id' => $variant->id,
                        ],
                        [
                            'user_id' => $admin->id,
                            'type' => InventoryMovementType::Restock,
                            'quantity' => $variantBlueprint['stock'],
                            'balance_after' => $variantBlueprint['stock'],
                            'reason' => 'Demo opening stock',
                            'notes' => 'Seeded by Phase 13 demo data.',
                        ],
                    );
                }

                $variants[$variantBlueprint['sku']] = $variant;
            }
        }

        $shippingZone = ShippingZone::query()->updateOrCreate(
            ['name' => 'Bangladesh metro'],
            [
                'countries' => ['BD'],
                'regions' => ['Dhaka', 'Chattogram'],
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $shippingMethods = [
            'dhaka-standard' => ShippingMethod::query()->updateOrCreate(
                ['code' => 'dhaka-standard'],
                [
                    'shipping_zone_id' => $shippingZone->id,
                    'name' => 'Dhaka standard',
                    'price' => 120,
                    'free_shipping_threshold' => 5000,
                    'estimated_days_min' => 2,
                    'estimated_days_max' => 4,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            ),
            'metro-express' => ShippingMethod::query()->updateOrCreate(
                ['code' => 'metro-express'],
                [
                    'shipping_zone_id' => $shippingZone->id,
                    'name' => 'Metro express',
                    'price' => 220,
                    'free_shipping_threshold' => null,
                    'estimated_days_min' => 1,
                    'estimated_days_max' => 2,
                    'is_active' => true,
                    'sort_order' => 2,
                ],
            ),
        ];

        return [
            'brands' => $brands,
            'categories' => $categories,
            'collections' => $collections,
            'attributes' => $attributes,
            'values' => $values,
            'products' => $products,
            'variants' => $variants,
            'shipping_methods' => $shippingMethods,
            'customers' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    protected function seedMarketing(array $catalog): void
    {
        $campaign = Campaign::query()->updateOrCreate(
            ['slug' => 'demo-launch'],
            [
                'name' => 'Demo launch',
                'description' => 'Original Amarvero opening campaign for seeded products.',
                'status' => ContentStatus::Published,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'banner_path' => self::PlaceholderImage,
            ],
        );

        $coupon = Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME500'],
            [
                'campaign_id' => $campaign->id,
                'name' => 'Welcome 500',
                'type' => CouponType::Cart,
                'discount_type' => DiscountType::Fixed,
                'value' => 500,
                'minimum_order_amount' => 3000,
                'maximum_discount_amount' => null,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'total_usage_limit' => 250,
                'per_customer_usage_limit' => 1,
                'first_order_only' => true,
                'is_active' => true,
            ],
        );

        $coupon->brands()->syncWithoutDetaching([
            $catalog['brands']['urban-thread']->id,
            $catalog['brands']['northline-sole']->id,
        ]);
        $coupon->categories()->syncWithoutDetaching([$catalog['categories']['sneakers']->id]);
        $coupon->products()->syncWithoutDetaching([$catalog['products']['city-runner']->id]);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => 'newsletter@example.test'],
            [
                'name' => 'Newsletter Demo',
                'status' => 'subscribed',
                'subscribed_at' => now()->subDays(3),
                'unsubscribed_at' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    protected function seedContent(array $catalog): void
    {
        AnnouncementBar::query()->updateOrCreate(
            ['name' => 'Demo launch shipping'],
            [
                'message' => 'Demo launch: free standard delivery over BDT 5,000.',
                'link_label' => 'Shop now',
                'link_url' => route('shop'),
                'status' => ContentStatus::Published,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'sort_order' => 1,
            ],
        );

        $menu = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'primary'],
            ['name' => 'Primary navigation', 'is_active' => true],
        );

        $this->menuItem($menu, 'Shop', route('shop'), 1);
        $this->menuItem($menu, 'Men', null, 2, $catalog['categories']['men']);
        $this->menuItem($menu, 'Women', null, 3, $catalog['categories']['women']);
        $this->menuItem($menu, 'Sale', route('sale'), 4);

        HeroSlide::query()->updateOrCreate(
            ['title' => 'Footwear built for daily movement'],
            [
                'subtitle' => 'Original demo merchandising for secure footwear commerce.',
                'image_path' => self::PlaceholderImage,
                'mobile_image_path' => self::PlaceholderImage,
                'cta_label' => 'Shop footwear',
                'cta_url' => route('shop'),
                'status' => ContentStatus::Published,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'sort_order' => 1,
                'meta' => ['image_alt' => 'Studio footwear still life for Amarvero'],
            ],
        );

        HomepageSection::query()->updateOrCreate(
            ['name' => 'Featured daily rotation'],
            [
                'type' => 'product_block',
                'title' => 'Daily rotation',
                'subtitle' => 'Seeded products ready for storefront discovery.',
                'content' => [
                    'description' => 'Browse featured and new-arrival footwear from the demo catalog.',
                    'cta_label' => 'View featured',
                    'cta_url' => route('featured'),
                ],
                'status' => ContentStatus::Published,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'sort_order' => 1,
            ],
        );

        PromotionalBanner::query()->updateOrCreate(
            ['name' => 'Home campaign banner'],
            [
                'campaign_id' => Campaign::query()->where('slug', 'demo-launch')->value('id'),
                'placement' => 'home',
                'title' => 'Welcome offer',
                'subtitle' => 'Use WELCOME500 on eligible demo pairs.',
                'image_path' => self::PlaceholderImage,
                'mobile_image_path' => self::PlaceholderImage,
                'cta_label' => 'Explore sale',
                'cta_url' => route('sale'),
                'status' => ContentStatus::Published,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'sort_order' => 1,
            ],
        );

        foreach ($this->staticPages() as $page) {
            StaticPage::query()->updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'body' => $page['body'],
                    'status' => ContentStatus::Published,
                    'published_at' => now()->subDay(),
                    'seo_title' => $page['title'].' | Amarvero',
                    'seo_description' => $page['description'],
                ],
            );
        }

        foreach ($this->faqs() as $index => $faq) {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'group' => $faq['group'],
                    'answer' => $faq['answer'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        foreach ($this->benefits() as $index => $benefit) {
            ServiceBenefit::query()->updateOrCreate(
                ['title' => $benefit['title']],
                [
                    'subtitle' => $benefit['subtitle'],
                    'icon' => $benefit['icon'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        foreach ($this->testimonials() as $index => $testimonial) {
            Testimonial::query()->updateOrCreate(
                ['name' => $testimonial['name']],
                [
                    'role' => $testimonial['role'],
                    'quote' => $testimonial['quote'],
                    'rating' => 5,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        StoreLocation::query()->updateOrCreate(
            ['name' => 'Amarvero Studio Dhaka'],
            [
                'phone' => '+8801700000000',
                'email' => 'care@amarvero.test',
                'line_one' => 'House 12, Demo Road',
                'line_two' => 'Level 3',
                'city' => 'Dhaka',
                'region' => 'Dhaka',
                'postal_code' => '1212',
                'country_code' => 'BD',
                'latitude' => 23.7805733,
                'longitude' => 90.2792397,
                'opening_hours' => ['Sat-Thu' => '10:00-20:00', 'Fri' => '14:00-20:00'],
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        foreach (['Instagram' => 'https://example.test/instagram', 'Facebook' => 'https://example.test/facebook'] as $label => $url) {
            SocialLink::query()->updateOrCreate(
                ['platform' => strtolower($label)],
                [
                    'label' => $label,
                    'url' => $url,
                    'is_active' => true,
                    'sort_order' => $label === 'Instagram' ? 1 : 2,
                ],
            );
        }

        $footer = FooterSection::query()->updateOrCreate(
            ['title' => 'Customer care'],
            ['is_active' => true, 'sort_order' => 1],
        );

        foreach (['Shipping policy' => 'shipping-policy', 'Return policy' => 'return-policy', 'Privacy policy' => 'privacy-policy'] as $label => $slug) {
            FooterLink::query()->updateOrCreate(
                ['footer_section_id' => $footer->id, 'label' => $label],
                [
                    'url' => route('pages.show', ['page' => $slug]),
                    'opens_new_tab' => false,
                    'is_active' => true,
                    'sort_order' => array_search($slug, ['shipping-policy', 'return-policy', 'privacy-policy'], true) + 1,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    protected function seedCustomersAndOrders(array $catalog, ?User $admin): void
    {
        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@example.test'],
            [
                'name' => 'Nadia Demo Customer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        CustomerAddress::query()->updateOrCreate(
            ['user_id' => $customer->id, 'type' => AddressType::Shipping->value],
            [
                'name' => $customer->name,
                'phone' => '+8801711111111',
                'line_one' => 'House 22, Customer Lane',
                'line_two' => null,
                'area' => 'Banani',
                'city' => 'Dhaka',
                'region' => 'Dhaka',
                'postal_code' => '1213',
                'country_code' => 'BD',
                'is_default_billing' => true,
                'is_default_shipping' => true,
            ],
        );

        $wishlist = Wishlist::query()->updateOrCreate(
            ['user_id' => $customer->id, 'name' => 'Wishlist'],
            ['is_default' => true],
        );

        WishlistItem::query()->updateOrCreate(
            ['wishlist_id' => $wishlist->id, 'product_id' => $catalog['products']['studio-sandal']->id],
            ['product_variant_id' => $catalog['variants']['AV-SSS-TAN-39']->id],
        );

        $cart = Cart::query()->updateOrCreate(
            ['user_id' => $customer->id, 'status' => CartStatus::Active->value],
            [
                'currency_code' => 'BDT',
                'expires_at' => now()->addDays(7),
            ],
        );

        CartItem::query()->updateOrCreate(
            ['cart_id' => $cart->id, 'product_variant_id' => $catalog['variants']['AV-JSS-WHT-39']->id],
            [
                'product_id' => $catalog['products']['junior-sprint']->id,
                'quantity' => 1,
                'unit_price_snapshot' => $catalog['products']['junior-sprint']->regular_price,
            ],
        );

        $order = Order::query()->updateOrCreate(
            ['order_number' => 'AV-DEMO-1001'],
            [
                'user_id' => $customer->id,
                'coupon_id' => Coupon::query()->where('code', 'WELCOME500')->value('id'),
                'shipping_method_id' => $catalog['shipping_methods']['dhaka-standard']->id,
                'customer_name' => $customer->name,
                'email' => $customer->email,
                'phone' => '+8801711111111',
                'status' => OrderStatus::Delivered,
                'payment_status' => PaymentStatus::Paid,
                'currency_code' => 'BDT',
                'subtotal' => 5200,
                'discount_total' => 500,
                'tax_total' => 0,
                'shipping_total' => 120,
                'grand_total' => 4820,
                'customer_note' => 'Please call before delivery.',
                'placed_at' => now()->subDays(5),
            ],
        );

        foreach ([AddressType::Shipping, AddressType::Billing] as $type) {
            OrderAddress::query()->updateOrCreate(
                ['order_id' => $order->id, 'type' => $type->value],
                [
                    'name' => $customer->name,
                    'phone' => '+8801711111111',
                    'line_one' => 'House 22, Customer Lane',
                    'line_two' => null,
                    'area' => 'Banani',
                    'city' => 'Dhaka',
                    'region' => 'Dhaka',
                    'postal_code' => '1213',
                    'country_code' => 'BD',
                ],
            );
        }

        $orderItem = OrderItem::query()->updateOrCreate(
            ['order_id' => $order->id, 'sku' => 'AV-CRL-BLK-41'],
            [
                'product_id' => $catalog['products']['city-runner']->id,
                'product_variant_id' => $catalog['variants']['AV-CRL-BLK-41']->id,
                'product_name' => $catalog['products']['city-runner']->name,
                'variant_name' => 'Black / EU 41',
                'quantity' => 1,
                'unit_price' => 5200,
                'discount_total' => 500,
                'tax_total' => 0,
                'line_total' => 4700,
            ],
        );

        OrderStatusEvent::query()->updateOrCreate(
            ['order_id' => $order->id, 'to_status' => OrderStatus::Delivered->value],
            [
                'user_id' => $admin?->id,
                'from_status' => OrderStatus::Shipped,
                'note' => 'Demo order delivered.',
            ],
        );

        $payment = Payment::query()->updateOrCreate(
            ['transaction_id' => 'DEMO-COD-1001'],
            [
                'order_id' => $order->id,
                'method' => PaymentMethod::CashOnDelivery,
                'status' => PaymentStatus::Paid,
                'amount' => 4820,
                'provider' => 'offline',
                'paid_at' => now()->subDays(4),
            ],
        );

        PaymentEvent::query()->updateOrCreate(
            ['payment_id' => $payment->id, 'to_status' => PaymentStatus::Paid->value],
            [
                'user_id' => $admin?->id,
                'from_status' => PaymentStatus::Pending,
                'note' => 'Demo cash payment collected.',
            ],
        );

        Shipment::query()->updateOrCreate(
            ['order_id' => $order->id, 'tracking_number' => 'AVTRK1001'],
            [
                'status' => ShipmentStatus::Delivered,
                'courier_name' => 'Amarvero Local Courier',
                'shipped_at' => now()->subDays(4),
                'delivered_at' => now()->subDays(3),
            ],
        );

        OrderNote::query()->updateOrCreate(
            ['order_id' => $order->id, 'body' => 'Demo support note for order operations.'],
            [
                'user_id' => $admin?->id,
                'is_customer_visible' => false,
            ],
        );

        $returnRequest = ReturnRequest::query()->updateOrCreate(
            ['order_id' => $order->id, 'user_id' => $customer->id],
            [
                'status' => ReturnStatus::Closed,
                'reason' => 'Demo return request closed after size confirmation.',
                'requested_refund_amount' => 0,
                'resolved_at' => now()->subDay(),
            ],
        );

        ReturnItem::query()->updateOrCreate(
            ['return_request_id' => $returnRequest->id, 'order_item_id' => $orderItem->id],
            [
                'quantity' => 1,
                'reason' => 'Size check demo',
            ],
        );

        Refund::query()->updateOrCreate(
            ['transaction_id' => 'DEMO-NO-REFUND-1001'],
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'return_request_id' => $returnRequest->id,
                'status' => RefundStatus::Cancelled,
                'amount' => 0,
                'reason' => 'No refund issued for closed demo return.',
                'refunded_at' => null,
            ],
        );

        CouponRedemption::query()->updateOrCreate(
            ['coupon_id' => (int) $order->coupon_id, 'order_id' => $order->id],
            [
                'user_id' => $customer->id,
                'discount_amount' => 500,
                'redeemed_at' => $order->placed_at,
            ],
        );

        ProductReview::query()->updateOrCreate(
            ['product_id' => $catalog['products']['city-runner']->id, 'user_id' => $customer->id],
            [
                'product_variant_id' => $catalog['variants']['AV-CRL-BLK-41']->id,
                'order_id' => $order->id,
                'rating' => 5,
                'title' => 'Clean daily pair',
                'body' => 'Comfortable enough for full workdays and weekend errands.',
                'status' => ReviewStatus::Approved,
                'is_verified_purchase' => true,
                'approved_at' => now()->subDays(2),
            ],
        );
    }

    protected function brand(string $name, string $slug, string $description, bool $featured, int $sortOrder): Brand
    {
        return Brand::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'logo_path' => null,
                'banner_path' => self::PlaceholderImage,
                'website_url' => null,
                'is_active' => true,
                'is_featured' => $featured,
                'sort_order' => $sortOrder,
                'seo_title' => $name.' footwear',
                'seo_description' => $description,
            ],
        );
    }

    protected function category(string $name, string $slug, ?Category $parent, int $sortOrder): Category
    {
        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent?->id,
                'name' => $name,
                'description' => 'Original '.$name.' footwear category for Amarvero demo products.',
                'image_path' => self::PlaceholderImage,
                'is_active' => true,
                'is_featured' => $parent === null,
                'sort_order' => $sortOrder,
                'seo_title' => $name.' shoes',
                'seo_description' => 'Browse '.$name.' footwear from the seeded Amarvero catalog.',
            ],
        );
    }

    protected function collection(string $name, string $slug, string $description, bool $featured, int $sortOrder): ProductCollection
    {
        return ProductCollection::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'image_path' => self::PlaceholderImage,
                'is_active' => true,
                'is_featured' => $featured,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'sort_order' => $sortOrder,
                'seo_title' => $name,
                'seo_description' => $description,
            ],
        );
    }

    protected function attribute(string $name, string $slug, string $type, bool $variantOption, bool $filterable, int $sortOrder): ProductAttribute
    {
        return ProductAttribute::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => $type,
                'is_variant_option' => $variantOption,
                'is_filterable' => $filterable,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );
    }

    protected function attributeValue(ProductAttribute $attribute, string $value, string $slug, ?string $colorHex, int $sortOrder): AttributeValue
    {
        return AttributeValue::query()->updateOrCreate(
            ['product_attribute_id' => $attribute->id, 'slug' => $slug],
            [
                'value' => $value,
                'display_value' => $value,
                'color_hex' => $colorHex,
                'image_path' => null,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );
    }

    protected function menuItem(NavigationMenu $menu, string $label, ?string $url, int $sortOrder, ?Model $linkable = null): void
    {
        NavigationMenuItem::query()->updateOrCreate(
            ['navigation_menu_id' => $menu->id, 'label' => $label],
            [
                'parent_id' => null,
                'type' => $linkable === null ? 'url' : 'model',
                'url' => $url,
                'linkable_type' => $linkable !== null ? $linkable::class : null,
                'linkable_id' => $linkable?->getKey(),
                'opens_new_tab' => false,
                'is_mega_menu' => in_array($label, ['Men', 'Women'], true),
                'is_active' => true,
                'sort_order' => $sortOrder,
                'meta' => ['desktop_visible' => true, 'mobile_visible' => true],
            ],
        );
    }

    /**
     * @return list<array{slug: string, title: string, body: string, description: string}>
     */
    protected function staticPages(): array
    {
        return [
            ['slug' => 'about', 'title' => 'About Amarvero', 'body' => 'Amarvero is an original demo footwear storefront built with Laravel, Livewire, and Tailwind CSS. It is designed to show catalog, checkout, account, operations, marketing, SEO, and content workflows.', 'description' => 'Learn about the original Amarvero demo footwear storefront.'],
            ['slug' => 'shipping-policy', 'title' => 'Shipping Policy', 'body' => 'Shipping rates are calculated at checkout from active admin shipping zones and methods. Demo delivery windows are estimates only.', 'description' => 'Shipping rates and delivery windows for Amarvero demo orders.'],
            ['slug' => 'return-policy', 'title' => 'Return Policy', 'body' => 'Eligible demo orders can be reviewed for returns within the configured return window. Items must remain unused and complete.', 'description' => 'Return policy information for Amarvero demo orders.'],
            ['slug' => 'refund-policy', 'title' => 'Refund Policy', 'body' => 'Refund records are managed by authorized administrators and linked to orders, payments, and return requests.', 'description' => 'Refund policy information for Amarvero demo orders.'],
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'body' => 'Amarvero demo data is fictional. Production deployments should configure privacy practices to match real operations.', 'description' => 'Privacy policy placeholder for Amarvero production configuration.'],
            ['slug' => 'terms', 'title' => 'Terms and Conditions', 'body' => 'These demo terms describe a development storefront and should be replaced before production launch.', 'description' => 'Terms and conditions placeholder for Amarvero.'],
        ];
    }

    /**
     * @return list<array{group: string, question: string, answer: string}>
     */
    protected function faqs(): array
    {
        return [
            ['group' => 'orders', 'question' => 'Can I track a demo order?', 'answer' => 'Yes. Seeded orders include status and shipment records visible in the admin and account areas.'],
            ['group' => 'returns', 'question' => 'How long is the return window?', 'answer' => 'The seeded settings use a seven-day return window that admins can adjust.'],
            ['group' => 'payments', 'question' => 'Are payment secrets stored in admin settings?', 'answer' => 'No. Demo settings avoid raw secrets and production credentials must live in environment configuration.'],
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, icon: string}>
     */
    protected function benefits(): array
    {
        return [
            ['title' => 'Secure checkout', 'subtitle' => 'Totals, discounts, shipping, and stock are recalculated server-side.', 'icon' => 'shield'],
            ['title' => 'Dynamic catalog', 'subtitle' => 'Products, variants, media, SEO, and merchandising are admin-managed.', 'icon' => 'sparkles'],
            ['title' => 'Operational audit trail', 'subtitle' => 'Orders include status events, payment events, notes, shipment, return, and refund records.', 'icon' => 'clipboard'],
        ];
    }

    /**
     * @return list<array{name: string, role: string, quote: string}>
     */
    protected function testimonials(): array
    {
        return [
            ['name' => 'Rafi Demo', 'role' => 'Returning customer', 'quote' => 'The seeded account flow makes it easy to inspect orders, addresses, and reviews.'],
            ['name' => 'Mira Demo', 'role' => 'Product manager', 'quote' => 'Catalog and content management are connected enough to test a realistic launch workflow.'],
        ];
    }
}
