<?php

use App\Enums\InventoryMovementType;
use App\Livewire\Admin\Catalog\ImageIndex;
use App\Livewire\Admin\Catalog\InventoryIndex;
use App\Livewire\Admin\Catalog\SizeGuideIndex;
use App\Livewire\Admin\Catalog\VariantIndex;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SizeGuide;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

function phaseFiveAdmin(TestCase $test, string $role = AdminPermissions::Admin): User
{
    $test->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('phase five admin routes are registered and protected by catalog permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.catalog.variants'))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    $this->actingAs($admin)
        ->get(route('admin.catalog.inventory'))
        ->assertOk()
        ->assertSee('Inventory');
});

test('admins can generate variants from assigned product option values', function () {
    $this->actingAs(phaseFiveAdmin($this));

    $product = Product::query()->create([
        'name' => 'City Runner',
        'slug' => 'city-runner',
        'base_sku' => 'CR-100',
    ]);

    $color = ProductAttribute::query()->create([
        'name' => 'Color',
        'slug' => 'color',
        'is_variant_option' => true,
    ]);

    $size = ProductAttribute::query()->create([
        'name' => 'Size',
        'slug' => 'size',
        'is_variant_option' => true,
    ]);

    $black = AttributeValue::query()->create([
        'product_attribute_id' => $color->id,
        'value' => 'Black',
        'slug' => 'black',
    ]);

    $tan = AttributeValue::query()->create([
        'product_attribute_id' => $color->id,
        'value' => 'Tan',
        'slug' => 'tan',
    ]);

    $size40 = AttributeValue::query()->create([
        'product_attribute_id' => $size->id,
        'value' => '40',
        'slug' => '40',
    ]);

    $size41 = AttributeValue::query()->create([
        'product_attribute_id' => $size->id,
        'value' => '41',
        'slug' => '41',
    ]);

    $product->attributeValues()->sync([$black->id, $tan->id, $size40->id, $size41->id]);

    Livewire::test(VariantIndex::class)
        ->set('generationProductId', $product->id)
        ->call('generateVariants')
        ->assertHasNoErrors();

    expect(ProductVariant::query()->where('product_id', $product->id)->count())->toBe(4)
        ->and(ProductVariant::query()->where('sku', 'CR-100-BLACK-40')->exists())->toBeTrue();

    Livewire::test(VariantIndex::class)
        ->call('create')
        ->set('form.product_id', $product->id)
        ->set('form.sku', 'CR-100-MANUAL')
        ->set('form.attribute_value_ids', [$black->id, $size40->id])
        ->call('save')
        ->assertHasErrors(['form.attribute_value_ids']);
});

test('admins can upload product images and enforce a single primary image per product', function () {
    Storage::fake('public');
    $this->actingAs(phaseFiveAdmin($this));

    $product = Product::query()->create([
        'name' => 'Gallery Runner',
        'slug' => 'gallery-runner',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'GR-100-BLK',
        'stock_quantity' => 3,
    ]);

    Livewire::test(ImageIndex::class)
        ->call('create')
        ->set('form.product_id', $product->id)
        ->set('form.product_variant_id', $variant->id)
        ->set('form.alt_text', 'Black runner side profile')
        ->set('form.is_primary', true)
        ->set('imageUpload', UploadedFile::fake()->image('runner.jpg', 800, 800))
        ->call('save')
        ->assertHasNoErrors();

    $firstImage = ProductImage::query()->where('product_id', $product->id)->firstOrFail();

    Storage::disk('public')->assertExists($firstImage->path);

    Livewire::test(ImageIndex::class)
        ->call('create')
        ->set('form.product_id', $product->id)
        ->set('form.path', 'products/manual-secondary.jpg')
        ->set('form.alt_text', 'Second product image')
        ->set('form.is_primary', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($firstImage->refresh()->is_primary)->toBeFalse()
        ->and(ProductImage::query()->where('product_id', $product->id)->where('is_primary', true)->count())->toBe(1);
});

test('admins can adjust inventory and every stock change records a movement', function () {
    $admin = phaseFiveAdmin($this);
    $this->actingAs($admin);

    $product = Product::query()->create([
        'name' => 'Stock Runner',
        'slug' => 'stock-runner',
    ]);

    $firstVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SR-100-BLK',
        'stock_quantity' => 10,
        'low_stock_threshold' => 3,
    ]);

    $secondVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SR-100-TAN',
        'stock_quantity' => 2,
    ]);

    Livewire::test(InventoryIndex::class)
        ->call('adjust', $firstVariant->id)
        ->set('adjustmentForm.type', InventoryMovementType::Adjustment->value)
        ->set('adjustmentForm.quantity', -3)
        ->set('adjustmentForm.reason', 'Cycle count correction')
        ->call('saveAdjustment')
        ->assertHasNoErrors();

    $movement = InventoryMovement::query()->where('product_variant_id', $firstVariant->id)->firstOrFail();

    expect($firstVariant->refresh()->stock_quantity)->toBe(7)
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->balance_after)->toBe(7)
        ->and($movement->user_id)->toBe($admin->id);

    Livewire::test(InventoryIndex::class)
        ->call('adjust', $secondVariant->id)
        ->set('adjustmentForm.quantity', -9)
        ->call('saveAdjustment')
        ->assertHasErrors(['adjustmentForm.quantity']);

    Livewire::test(InventoryIndex::class)
        ->set('selectedVariantIds', [$firstVariant->id, $secondVariant->id])
        ->set('bulkMovementType', InventoryMovementType::Restock->value)
        ->set('bulkAdjustmentQuantity', 2)
        ->set('bulkAdjustmentReason', 'New stock receipt')
        ->call('bulkAdjustSelected')
        ->assertHasNoErrors();

    expect($firstVariant->refresh()->stock_quantity)->toBe(9)
        ->and($secondVariant->refresh()->stock_quantity)->toBe(4)
        ->and(InventoryMovement::query()->count())->toBe(3);
});

test('admins can manage size guides and assign them to products', function () {
    $this->actingAs(phaseFiveAdmin($this));

    $brand = Brand::query()->create(['name' => 'Fit Lab', 'slug' => 'fit-lab']);
    $category = Category::query()->create(['name' => 'Running Shoes', 'slug' => 'running-shoes']);
    $product = Product::query()->create(['name' => 'Fit Runner', 'slug' => 'fit-runner']);

    Livewire::test(SizeGuideIndex::class)
        ->call('create')
        ->set('form.brand_id', $brand->id)
        ->set('form.category_id', $category->id)
        ->set('form.name', 'Men Running Size Guide')
        ->set('form.content', 'Measure feet at the end of the day.')
        ->set('form.measurements_text', "EU 40: Foot length 25.5 cm\nEU 41: Foot length 26 cm")
        ->set('form.product_ids', [$product->id])
        ->call('save')
        ->assertHasNoErrors();

    $sizeGuide = SizeGuide::query()->with('products')->where('slug', 'men-running-size-guide')->firstOrFail();

    expect($sizeGuide->brand_id)->toBe($brand->id)
        ->and($sizeGuide->category_id)->toBe($category->id)
        ->and($sizeGuide->measurements)->toHaveCount(2)
        ->and($sizeGuide->products)->toHaveCount(1);
});

test('support users cannot access product variant management', function () {
    $this->actingAs(phaseFiveAdmin($this, AdminPermissions::CustomerSupport));

    $this->get(route('admin.catalog.variants'))
        ->assertForbidden();
});
