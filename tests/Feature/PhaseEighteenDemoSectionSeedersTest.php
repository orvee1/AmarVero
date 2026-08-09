<?php

use App\Models\AnnouncementBar;
use App\Models\Campaign;
use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\Product;
use App\Models\PromotionalBanner;
use App\Models\ServiceBenefit;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\StaticPage;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoCustomerOrderSeeder;
use Database\Seeders\DemoMarketingSeeder;
use Database\Seeders\DemoStorefrontContentSeeder;
use Database\Seeders\DemoStoreSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;

test('demo section seeders populate every visible storefront area', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(AdminUserSeeder::class);
    $this->seed(DemoStoreSettingsSeeder::class);
    $this->seed(DemoCatalogSeeder::class);
    $this->seed(DemoMarketingSeeder::class);
    $this->seed(DemoStorefrontContentSeeder::class);
    $this->seed(DemoCustomerOrderSeeder::class);

    expect(SiteSetting::count())->toBeGreaterThanOrEqual(19)
        ->and(Product::published()->count())->toBe(4)
        ->and(Campaign::where('slug', 'demo-launch')->exists())->toBeTrue()
        ->and(AnnouncementBar::count())->toBe(1)
        ->and(NavigationMenu::where('slug', 'primary')->exists())->toBeTrue()
        ->and(NavigationMenuItem::count())->toBe(6)
        ->and(HeroSlide::count())->toBe(3)
        ->and(HomepageSection::count())->toBe(6)
        ->and(PromotionalBanner::count())->toBe(3)
        ->and(ServiceBenefit::count())->toBe(6)
        ->and(Testimonial::count())->toBe(4)
        ->and(StoreLocation::count())->toBe(2)
        ->and(SocialLink::count())->toBe(4)
        ->and(StaticPage::whereIn('slug', ['about', 'shipping-policy', 'size-guide', 'care-guide'])->count())->toBe(4)
        ->and(FooterSection::count())->toBe(4)
        ->and(FooterLink::count())->toBe(16);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Original Amarvero storefront')
        ->assertSee('Footwear built for daily movement')
        ->assertSee('New this week')
        ->assertSee('Office ready')
        ->assertSee('Weekend light')
        ->assertSee('Welcome offer')
        ->assertSee('Daily pairs stocked')
        ->assertSee('Customer communication')
        ->assertSee('Ayaan Demo')
        ->assertSee('Amarvero Pickup Chattogram')
        ->assertSee('Size guide')
        ->assertSee('Care guide');
});
