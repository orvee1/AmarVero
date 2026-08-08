<?php

use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;

test('storefront home renders the Amarvero storefront shell', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Footwear built for daily movement.')
        ->assertSee('images/storefront/hero-footwear.png')
        ->assertSee('Create account');
});

test('customer dashboard renders real account metrics', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Account overview')
        ->assertSee('Orders')
        ->assertSee('No orders yet');
});

test('admin overview is protected and renders real operational metrics', function () {
    $this->seed(RolePermissionSeeder::class);

    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin overview')
        ->assertSee('Products')
        ->assertSee('Recent orders')
        ->assertSee('Live data');
});
