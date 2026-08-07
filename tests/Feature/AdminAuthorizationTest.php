<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\StaticPage;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function registerAdminProbeRoute(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    Route::middleware(['web', 'auth', 'verified', 'admin'])->get(
        '/phase-two-admin-probe',
        fn () => response('ok'),
    );

    $registered = true;
}

test('role permission seeder creates the centralized admin permission matrix', function () {
    expect(Permission::count())->toBe(count(AdminPermissions::all()))
        ->and(Role::count())->toBe(6)
        ->and(Role::findByName(AdminPermissions::SuperAdmin)->permissions)->toHaveCount(count(AdminPermissions::all()))
        ->and(Role::findByName(AdminPermissions::ProductManager)->hasPermissionTo('products.update'))->toBeTrue()
        ->and(Role::findByName(AdminPermissions::ProductManager)->hasPermissionTo('payment-settings.update'))->toBeFalse();
});

test('admin middleware rejects ordinary customers and allows admin roles', function () {
    registerAdminProbeRoute();

    $customer = User::factory()->create();
    $productManager = User::factory()->create();
    $productManager->assignRole(AdminPermissions::ProductManager);

    $this->actingAs($customer)
        ->get('/phase-two-admin-probe')
        ->assertForbidden();

    $this->actingAs($productManager)
        ->get('/phase-two-admin-probe')
        ->assertOk()
        ->assertSee('ok');
});

test('catalog policies enforce product manager boundaries', function () {
    $productManager = User::factory()->create();
    $productManager->assignRole(AdminPermissions::ProductManager);

    expect(Gate::forUser($productManager)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($productManager)->allows('update', new Product))->toBeTrue()
        ->and(Gate::forUser($productManager)->allows('delete', new Product))->toBeFalse()
        ->and(Gate::forUser($productManager)->allows('moderate', ProductReview::class))->toBeTrue()
        ->and(Gate::forUser($productManager)->allows('updatePayment', new Order))->toBeFalse();
});

test('order and content roles are scoped to their modules', function () {
    $orderManager = User::factory()->create();
    $orderManager->assignRole(AdminPermissions::OrderManager);

    $contentManager = User::factory()->create();
    $contentManager->assignRole(AdminPermissions::ContentManager);

    expect(Gate::forUser($orderManager)->allows('updatePayment', new Order))->toBeTrue()
        ->and(Gate::forUser($orderManager)->allows('update', new Product))->toBeFalse()
        ->and(Gate::forUser($contentManager)->allows('update', new StaticPage))->toBeTrue()
        ->and(Gate::forUser($contentManager)->allows('update', new Product))->toBeFalse();
});

test('super admin bypasses policy checks through the global gate override', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(AdminPermissions::SuperAdmin);

    expect(Gate::forUser($superAdmin)->allows('delete', new Product))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('updatePayment', new Order))->toBeTrue()
        ->and($superAdmin->can('payment-settings.update'))->toBeTrue();
});
