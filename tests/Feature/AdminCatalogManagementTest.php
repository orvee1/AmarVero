<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Catalog\AttributeIndex;
use App\Livewire\Admin\Catalog\BrandIndex;
use App\Livewire\Admin\Catalog\CategoryIndex;
use App\Livewire\Admin\Catalog\CollectionIndex;
use App\Livewire\Admin\Catalog\ProductIndex;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCollection;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Tests\TestCase;

function catalogAdmin(TestCase $test, string $role = AdminPermissions::Admin): User
{
    $test->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('catalog admin routes require catalog authorization', function () {
    $this->seed(RolePermissionSeeder::class);

    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.catalog.brands'))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    $this->actingAs($admin)
        ->get(route('admin.catalog.products'))
        ->assertOk()
        ->assertSee('Products')
        ->assertSee('New product');
});

test('admins can manage brand, category, and collection records', function () {
    $this->actingAs(catalogAdmin($this));

    Livewire::test(BrandIndex::class)
        ->call('create')
        ->set('form.name', 'Stride House')
        ->set('form.is_featured', true)
        ->call('save')
        ->assertHasNoErrors();

    $brand = Brand::query()->where('slug', 'stride-house')->firstOrFail();

    Livewire::test(CategoryIndex::class)
        ->call('create')
        ->set('form.name', 'Men Sneakers')
        ->call('save')
        ->assertHasNoErrors();

    $parent = Category::query()->where('slug', 'men-sneakers')->firstOrFail();

    Livewire::test(CategoryIndex::class)
        ->call('create')
        ->set('form.parent_id', $parent->id)
        ->set('form.name', 'Leather Sneakers')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(CollectionIndex::class)
        ->call('create')
        ->set('form.name', 'City Walk Edit')
        ->set('form.is_active', true)
        ->set('form.starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('form.ends_at', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($brand->refresh()->is_featured)->toBeTrue()
        ->and(Category::query()->where('slug', 'leather-sneakers')->first()->parent_id)->toBe($parent->id)
        ->and(ProductCollection::query()->where('slug', 'city-walk-edit')->exists())->toBeTrue();
});

test('product managers cannot delete catalog records without delete permission', function () {
    $this->actingAs(catalogAdmin($this, AdminPermissions::ProductManager));

    $brand = Brand::query()->create([
        'name' => 'Delete Guard',
        'slug' => 'delete-guard',
    ]);

    Livewire::test(BrandIndex::class)
        ->call('delete', $brand->id);

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'slug' => 'delete-guard',
    ]);
});

test('admins can manage attributes and scoped values', function () {
    $this->actingAs(catalogAdmin($this));

    Livewire::test(AttributeIndex::class)
        ->call('createAttribute')
        ->set('attributeForm.name', 'Color')
        ->set('attributeForm.type', 'color')
        ->set('attributeForm.is_variant_option', true)
        ->call('saveAttribute')
        ->assertHasNoErrors();

    $attribute = ProductAttribute::query()->where('slug', 'color')->firstOrFail();

    Livewire::test(AttributeIndex::class)
        ->call('createValue', $attribute->id)
        ->set('valueForm.product_attribute_id', $attribute->id)
        ->set('valueForm.value', 'Black')
        ->set('valueForm.display_value', 'Classic Black')
        ->set('valueForm.color_hex', '#111111')
        ->call('saveValue')
        ->assertHasNoErrors();

    $value = AttributeValue::query()->where('slug', 'black')->firstOrFail();

    expect($attribute->refresh()->is_variant_option)->toBeTrue()
        ->and($value->product_attribute_id)->toBe($attribute->id)
        ->and($value->display_value)->toBe('Classic Black');
});

test('admins can create products with catalog relationships and merchandising flags', function () {
    $this->actingAs(catalogAdmin($this));

    $brand = Brand::query()->create(['name' => 'Amarvero Lab', 'slug' => 'amarvero-lab']);
    $category = Category::query()->create(['name' => 'Daily Sneakers', 'slug' => 'daily-sneakers']);
    $collection = ProductCollection::query()->create(['name' => 'Office Ready', 'slug' => 'office-ready']);
    $attribute = ProductAttribute::query()->create(['name' => 'Material', 'slug' => 'material']);
    $attributeValue = AttributeValue::query()->create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Leather',
        'slug' => 'leather',
    ]);

    Livewire::test(ProductIndex::class)
        ->call('create')
        ->set('form.brand_id', $brand->id)
        ->set('form.name', 'Urban Low Sneaker')
        ->set('form.base_sku', 'ULS-001')
        ->set('form.status', ProductStatus::Published->value)
        ->set('form.regular_price', '4500')
        ->set('form.sale_price', '3990')
        ->set('form.category_ids', [$category->id])
        ->set('form.collection_ids', [$collection->id])
        ->set('form.attribute_value_ids', [$attributeValue->id])
        ->set('form.is_featured', true)
        ->set('form.is_new_arrival', true)
        ->set('form.seo_title', 'Urban Low Sneaker')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()
        ->with(['categories', 'collections', 'attributeValues'])
        ->where('slug', 'urban-low-sneaker')
        ->firstOrFail();

    expect($product->brand_id)->toBe($brand->id)
        ->and($product->status)->toBe(ProductStatus::Published)
        ->and($product->published_at)->not->toBeNull()
        ->and($product->is_featured)->toBeTrue()
        ->and($product->is_new_arrival)->toBeTrue()
        ->and($product->categories)->toHaveCount(1)
        ->and($product->collections)->toHaveCount(1)
        ->and($product->attributeValues)->toHaveCount(1);
});

test('product validation blocks unsafe sale pricing and bulk status updates work', function () {
    $this->actingAs(catalogAdmin($this));

    Livewire::test(ProductIndex::class)
        ->call('create')
        ->set('form.name', 'Invalid Sale Product')
        ->set('form.regular_price', '100')
        ->set('form.sale_price', '125')
        ->call('save')
        ->assertHasErrors(['form.sale_price']);

    $first = Product::query()->create([
        'name' => 'Bulk One',
        'slug' => 'bulk-one',
        'status' => ProductStatus::Draft,
    ]);

    $second = Product::query()->create([
        'name' => 'Bulk Two',
        'slug' => 'bulk-two',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::test(ProductIndex::class)
        ->set('selectedProductIds', [$first->id, $second->id])
        ->set('bulkStatus', ProductStatus::Published->value)
        ->call('updateSelectedStatus')
        ->assertHasNoErrors();

    expect($first->refresh()->status)->toBe(ProductStatus::Published)
        ->and($first->published_at)->not->toBeNull()
        ->and($second->refresh()->status)->toBe(ProductStatus::Published);
});
