<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('role and permission seeder creates the admin authorization foundation', function () {
    $this->seed(RolePermissionSeeder::class);

    $superAdmin = Role::findByName('Super Admin');
    $productManager = Role::findByName('Product Manager');
    $paymentSettings = Permission::findByName('payment-settings.update');

    expect(Role::count())->toBe(6)
        ->and(Permission::where('guard_name', 'web')->count())->toBeGreaterThan(40)
        ->and($superAdmin->hasPermissionTo($paymentSettings))->toBeTrue();

    $user = User::factory()->create();
    $user->assignRole($productManager);

    expect($user->hasPermissionTo('products.update'))->toBeTrue()
        ->and($user->hasPermissionTo('payment-settings.update'))->toBeFalse();
});
