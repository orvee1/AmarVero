<?php

use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolePermissionSeeder;

function phaseFifteenAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(AdminPermissions::Admin);

    return $admin;
}

test('admin shell exposes keyboard friendly mobile navigation and quick navigation search', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseFifteenAdmin();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Skip to admin content')
        ->assertSee('x-on:keydown.escape.window="closeSidebar()"', false)
        ->assertSee('x-effect="document.body.classList.toggle(\'overflow-hidden\', sidebarOpen)"', false)
        ->assertSee('aria-controls="admin-sidebar"', false)
        ->assertSee('x-bind:aria-expanded="sidebarOpen.toString()"', false)
        ->assertSee('role="search"', false)
        ->assertSee('Search admin navigation')
        ->assertSee('admin-quick-navigation-options')
        ->assertSee(route('admin.catalog.products'), false);
});

test('admin data tables expose focusable scroll regions and mobile scroll hints', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = phaseFifteenAdmin();

    $this->actingAs($admin)
        ->get(route('admin.catalog.products'))
        ->assertOk()
        ->assertSee('role="region"', false)
        ->assertSee('aria-label="Products table"', false)
        ->assertSee('Scroll sideways to review product status, pricing, catalog counts, and actions.')
        ->assertSee('tabindex="0"', false);

    $this->actingAs($admin)
        ->get(route('admin.operations.orders'))
        ->assertOk()
        ->assertSee('role="region"', false)
        ->assertSee('aria-label="Orders table"', false)
        ->assertSee('Scroll sideways to review customer, status, totals, and actions.')
        ->assertSee('tabindex="0"', false);
});
