<?php

use App\Enums\ContentStatus;
use App\Livewire\Admin\Content\AnnouncementBarIndex;
use App\Livewire\Admin\Content\ContentLibraryIndex;
use App\Livewire\Admin\Content\FooterContentIndex;
use App\Livewire\Admin\Content\HomepageContentIndex;
use App\Livewire\Admin\Content\NavigationMenuIndex;
use App\Models\AnnouncementBar;
use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\PromotionalBanner;
use App\Models\ServiceBenefit;
use App\Models\SocialLink;
use App\Models\StaticPage;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Tests\TestCase;

function phaseSixAdmin(TestCase $test, string $role = AdminPermissions::Admin): User
{
    $test->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('phase six content admin routes are protected and available to admins', function () {
    $this->seed(RolePermissionSeeder::class);

    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.content.homepage'))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    $this->actingAs($admin)
        ->get(route('admin.content.announcements'))
        ->assertOk()
        ->assertSee('Announcement bars');
});

test('admins can manage announcement, navigation, homepage, and banner content', function () {
    $this->actingAs(phaseSixAdmin($this));

    Livewire::test(AnnouncementBarIndex::class)
        ->call('create')
        ->set('form.name', 'Launch top bar')
        ->set('form.message', 'Phase six launch savings')
        ->set('form.link_label', 'Shop launch')
        ->set('form.link_url', '/launch')
        ->set('form.status', ContentStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(NavigationMenuIndex::class)
        ->call('createMenu')
        ->set('menuForm.name', 'Primary')
        ->set('menuForm.slug', 'primary')
        ->call('saveMenu')
        ->assertHasNoErrors();

    $menu = NavigationMenu::query()->where('slug', 'primary')->firstOrFail();

    Livewire::test(NavigationMenuIndex::class)
        ->set('selectedMenuId', (string) $menu->id)
        ->call('createItem')
        ->set('itemForm.navigation_menu_id', $menu->id)
        ->set('itemForm.label', 'New Arrivals')
        ->set('itemForm.type', 'url')
        ->set('itemForm.url', '/new-arrivals')
        ->set('itemForm.is_mega_menu', true)
        ->set('itemForm.promo_title', 'Fresh drops')
        ->call('saveItem')
        ->assertHasNoErrors();

    Livewire::test(HomepageContentIndex::class)
        ->call('createSlide')
        ->set('slideForm.title', 'Move through the city')
        ->set('slideForm.subtitle', 'Daily footwear edited by Amarvero')
        ->set('slideForm.image_path', 'hero/city.jpg')
        ->set('slideForm.status', ContentStatus::Published->value)
        ->set('slideForm.meta_text', 'image_alt: City sneaker')
        ->call('saveSlide')
        ->assertHasNoErrors()
        ->call('createSection')
        ->set('sectionForm.name', 'Featured benefits')
        ->set('sectionForm.type', 'editorial')
        ->set('sectionForm.title', 'Built for long days')
        ->set('sectionForm.content_text', 'description: Cushioned styles for commutes')
        ->set('sectionForm.status', ContentStatus::Published->value)
        ->call('saveSection')
        ->assertHasNoErrors()
        ->call('createBanner')
        ->set('bannerForm.name', 'Home launch banner')
        ->set('bannerForm.placement', 'home')
        ->set('bannerForm.title', 'Launch edit')
        ->set('bannerForm.image_path', 'banners/launch.jpg')
        ->set('bannerForm.status', ContentStatus::Published->value)
        ->call('saveBanner')
        ->assertHasNoErrors();

    expect(AnnouncementBar::query()->where('message', 'Phase six launch savings')->exists())->toBeTrue()
        ->and(NavigationMenuItem::query()->where('label', 'New Arrivals')->where('is_mega_menu', true)->exists())->toBeTrue()
        ->and(HeroSlide::query()->where('title', 'Move through the city')->exists())->toBeTrue()
        ->and(HomepageSection::query()->where('name', 'Featured benefits')->exists())->toBeTrue()
        ->and(PromotionalBanner::query()->where('name', 'Home launch banner')->exists())->toBeTrue();
});

test('storefront renders only active scheduled content plus dynamic nav and footer', function () {
    $page = StaticPage::query()->create([
        'title' => 'Return Policy',
        'slug' => 'return-policy',
        'body' => 'Returns are accepted within the configured window.',
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    AnnouncementBar::query()->create([
        'name' => 'Active announcement',
        'message' => 'Free delivery this week',
        'status' => ContentStatus::Published,
        'starts_at' => now()->subHour(),
        'sort_order' => 1,
    ]);

    AnnouncementBar::query()->create([
        'name' => 'Future announcement',
        'message' => 'Future-only message',
        'status' => ContentStatus::Published,
        'starts_at' => now()->addDay(),
    ]);

    $menu = NavigationMenu::query()->create([
        'name' => 'Primary',
        'slug' => 'primary',
        'is_active' => true,
    ]);

    NavigationMenuItem::query()->create([
        'navigation_menu_id' => $menu->id,
        'label' => 'Policy',
        'type' => 'page',
        'linkable_type' => StaticPage::class,
        'linkable_id' => $page->id,
        'is_active' => true,
    ]);

    HeroSlide::query()->create([
        'title' => 'Database hero',
        'subtitle' => 'Rendered from content tables',
        'image_path' => 'hero/database.jpg',
        'status' => ContentStatus::Published,
        'starts_at' => now()->subDay(),
    ]);

    HomepageSection::query()->create([
        'name' => 'Database section',
        'type' => 'editorial',
        'title' => 'Editor picked comfort',
        'content' => ['description' => 'Database controlled copy'],
        'status' => ContentStatus::Published,
    ]);

    PromotionalBanner::query()->create([
        'name' => 'Home banner',
        'placement' => 'home',
        'title' => 'Member week',
        'image_path' => 'banners/member.jpg',
        'status' => ContentStatus::Published,
    ]);

    ServiceBenefit::query()->create([
        'title' => 'Easy exchanges',
        'subtitle' => 'Support-ready policies',
        'is_active' => true,
    ]);

    $footerSection = FooterSection::query()->create([
        'title' => 'Customer care',
        'is_active' => true,
    ]);

    FooterLink::query()->create([
        'footer_section_id' => $footerSection->id,
        'label' => 'Returns',
        'url' => route('pages.show', ['page' => $page->slug]),
        'is_active' => true,
    ]);

    SocialLink::query()->create([
        'label' => 'Instagram',
        'platform' => 'instagram',
        'url' => 'https://example.com/instagram',
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Free delivery this week')
        ->assertDontSee('Future-only message')
        ->assertSee('Policy')
        ->assertSee('Database hero')
        ->assertSee('Editor picked comfort')
        ->assertSee('Member week')
        ->assertSee('Easy exchanges')
        ->assertSee('Customer care')
        ->assertSee('Instagram');
});

test('content library and footer modules persist cms records', function () {
    $this->actingAs(phaseSixAdmin($this));

    Livewire::test(ContentLibraryIndex::class)
        ->call('createPage')
        ->set('pageForm.title', 'Privacy Policy')
        ->set('pageForm.body', 'We protect customer data and document policy changes.')
        ->set('pageForm.status', ContentStatus::Published->value)
        ->call('savePage')
        ->assertHasNoErrors()
        ->call('createFaq')
        ->set('faqForm.question', 'How long does delivery take?')
        ->set('faqForm.answer', 'Delivery estimates are shown during checkout.')
        ->call('saveFaq')
        ->assertHasNoErrors()
        ->call('createTestimonial')
        ->set('testimonialForm.name', 'Nadia')
        ->set('testimonialForm.quote', 'Comfortable through an entire work day.')
        ->set('testimonialForm.rating', 5)
        ->call('saveTestimonial')
        ->assertHasNoErrors()
        ->call('createBenefit')
        ->set('benefitForm.title', 'Secure checkout')
        ->set('benefitForm.subtitle', 'Provider-ready payment flows')
        ->call('saveBenefit')
        ->assertHasNoErrors()
        ->call('createLocation')
        ->set('locationForm.name', 'Dhanmondi Studio')
        ->set('locationForm.line_one', 'Road 12')
        ->set('locationForm.city', 'Dhaka')
        ->set('locationForm.country_code', 'BD')
        ->call('saveLocation')
        ->assertHasNoErrors();

    $footerComponent = Livewire::test(FooterContentIndex::class)
        ->call('createSection')
        ->set('sectionForm.title', 'Policies')
        ->call('saveSection')
        ->assertHasNoErrors();

    $footerSection = FooterSection::query()->where('title', 'Policies')->firstOrFail();

    $footerComponent
        ->call('createLink')
        ->set('linkForm.footer_section_id', $footerSection->id)
        ->set('linkForm.label', 'Privacy')
        ->set('linkForm.url', '/pages/privacy-policy')
        ->call('saveLink')
        ->assertHasNoErrors()
        ->call('createSocial')
        ->set('socialForm.label', 'Facebook')
        ->set('socialForm.platform', 'facebook')
        ->set('socialForm.url', 'https://example.com/facebook')
        ->call('saveSocial')
        ->assertHasNoErrors();

    expect(StaticPage::query()->where('slug', 'privacy-policy')->exists())->toBeTrue()
        ->and(ServiceBenefit::query()->where('title', 'Secure checkout')->exists())->toBeTrue()
        ->and(FooterLink::query()->where('label', 'Privacy')->exists())->toBeTrue()
        ->and(SocialLink::query()->where('platform', 'facebook')->exists())->toBeTrue();
});

test('published cms pages render and unpublished pages do not', function () {
    StaticPage::query()->create([
        'title' => 'Shipping Policy',
        'slug' => 'shipping-policy',
        'body' => 'Shipping timelines vary by location.',
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    StaticPage::query()->create([
        'title' => 'Draft Policy',
        'slug' => 'draft-policy',
        'body' => 'Draft-only content.',
        'status' => ContentStatus::Draft,
    ]);

    $this->get(route('pages.show', ['page' => 'shipping-policy']))
        ->assertOk()
        ->assertSee('Shipping Policy')
        ->assertSee('Shipping timelines vary by location.');

    $this->get(route('pages.show', ['page' => 'draft-policy']))
        ->assertNotFound();
});
