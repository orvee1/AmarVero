<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\OrderConfirmationController;
use App\Http\Controllers\Storefront\WishlistItemController;
use App\Http\Controllers\StorefrontController;
use App\Livewire\Admin\Catalog\AttributeIndex;
use App\Livewire\Admin\Catalog\BrandIndex;
use App\Livewire\Admin\Catalog\CategoryIndex;
use App\Livewire\Admin\Catalog\CollectionIndex;
use App\Livewire\Admin\Catalog\ImageIndex;
use App\Livewire\Admin\Catalog\InventoryIndex;
use App\Livewire\Admin\Catalog\ProductIndex;
use App\Livewire\Admin\Catalog\SizeGuideIndex;
use App\Livewire\Admin\Catalog\VariantIndex;
use App\Livewire\Admin\Content\AnnouncementBarIndex;
use App\Livewire\Admin\Content\ContentLibraryIndex;
use App\Livewire\Admin\Content\FooterContentIndex;
use App\Livewire\Admin\Content\HomepageContentIndex;
use App\Livewire\Admin\Content\NavigationMenuIndex;
use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ProductListing;
use App\Livewire\Storefront\ProductShow;
use App\Livewire\Storefront\WishlistPage;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontController::class)->name('home');
Route::get('pages/{page:slug}', [StorefrontController::class, 'page'])->name('pages.show');
Route::livewire('shop', ProductListing::class)->name('shop');
Route::livewire('search', ProductListing::class)->name('search');
Route::livewire('sale', ProductListing::class)->name('sale');
Route::livewire('featured', ProductListing::class)->name('featured');
Route::livewire('new-arrivals', ProductListing::class)->name('new-arrivals');
Route::livewire('best-sellers', ProductListing::class)->name('best-sellers');
Route::livewire('gender/{slug}', ProductListing::class)->name('gender.show');
Route::livewire('categories/{slug}', ProductListing::class)->name('categories.show');
Route::livewire('brands/{slug}', ProductListing::class)->name('brands.show');
Route::livewire('collections/{slug}', ProductListing::class)->name('collections.show');
Route::livewire('products/{product:slug}', ProductShow::class)->name('products.show');
Route::livewire('cart', CartPage::class)->name('cart');
Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::livewire('checkout', CheckoutPage::class)->name('checkout');
Route::get('orders/{order:order_number}/thank-you', OrderConfirmationController::class)->name('checkout.thank-you');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('wishlist', WishlistPage::class)->name('wishlist');
    Route::post('wishlist/items', [WishlistItemController::class, 'store'])->name('wishlist.items.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::livewire('catalog/brands', BrandIndex::class)->name('catalog.brands');
        Route::livewire('catalog/categories', CategoryIndex::class)->name('catalog.categories');
        Route::livewire('catalog/collections', CollectionIndex::class)->name('catalog.collections');
        Route::livewire('catalog/attributes', AttributeIndex::class)->name('catalog.attributes');
        Route::livewire('catalog/products', ProductIndex::class)->name('catalog.products');
        Route::livewire('catalog/variants', VariantIndex::class)->name('catalog.variants');
        Route::livewire('catalog/images', ImageIndex::class)->name('catalog.images');
        Route::livewire('catalog/inventory', InventoryIndex::class)->name('catalog.inventory');
        Route::livewire('catalog/size-guides', SizeGuideIndex::class)->name('catalog.size-guides');

        Route::livewire('content/announcements', AnnouncementBarIndex::class)->name('content.announcements');
        Route::livewire('content/navigation', NavigationMenuIndex::class)->name('content.navigation');
        Route::livewire('content/homepage', HomepageContentIndex::class)->name('content.homepage');
        Route::livewire('content/library', ContentLibraryIndex::class)->name('content.library');
        Route::livewire('content/footer', FooterContentIndex::class)->name('content.footer');
    });

require __DIR__.'/settings.php';
