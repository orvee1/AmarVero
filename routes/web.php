<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Admin\Catalog\AttributeIndex;
use App\Livewire\Admin\Catalog\BrandIndex;
use App\Livewire\Admin\Catalog\CategoryIndex;
use App\Livewire\Admin\Catalog\CollectionIndex;
use App\Livewire\Admin\Catalog\ImageIndex;
use App\Livewire\Admin\Catalog\InventoryIndex;
use App\Livewire\Admin\Catalog\ProductIndex;
use App\Livewire\Admin\Catalog\SizeGuideIndex;
use App\Livewire\Admin\Catalog\VariantIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
    });

require __DIR__.'/settings.php';
