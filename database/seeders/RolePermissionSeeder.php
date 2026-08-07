<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'dashboard.view',
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'collections.view',
            'collections.create',
            'collections.update',
            'collections.delete',
            'attributes.view',
            'attributes.create',
            'attributes.update',
            'attributes.delete',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'product-variants.manage',
            'product-images.manage',
            'inventory.view',
            'inventory.update',
            'inventory-movements.view',
            'size-guides.manage',
            'reviews.view',
            'reviews.moderate',
            'orders.view',
            'orders.update-status',
            'orders.update-payment',
            'orders.cancel',
            'orders.refund',
            'orders.export',
            'orders.print-invoice',
            'customers.view',
            'customers.update',
            'customer-notes.manage',
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',
            'campaigns.manage',
            'newsletter.manage',
            'hero-sliders.manage',
            'banners.manage',
            'homepage-sections.manage',
            'navigation-menus.manage',
            'pages.manage',
            'faqs.manage',
            'testimonials.manage',
            'store-locations.manage',
            'service-benefits.manage',
            'settings.view',
            'settings.update',
            'shipping-settings.update',
            'payment-settings.update',
            'seo.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'Super Admin' => $permissions,
            'Admin' => array_values(array_diff($permissions, ['payment-settings.update'])),
            'Product Manager' => [
                'admin.access',
                'dashboard.view',
                'brands.view',
                'brands.create',
                'brands.update',
                'categories.view',
                'categories.create',
                'categories.update',
                'collections.view',
                'collections.create',
                'collections.update',
                'attributes.view',
                'attributes.create',
                'attributes.update',
                'products.view',
                'products.create',
                'products.update',
                'product-variants.manage',
                'product-images.manage',
                'inventory.view',
                'inventory.update',
                'inventory-movements.view',
                'size-guides.manage',
                'reviews.view',
                'reviews.moderate',
            ],
            'Order Manager' => [
                'admin.access',
                'dashboard.view',
                'orders.view',
                'orders.update-status',
                'orders.update-payment',
                'orders.cancel',
                'orders.refund',
                'orders.export',
                'orders.print-invoice',
                'customers.view',
                'customer-notes.manage',
                'inventory.view',
            ],
            'Content Manager' => [
                'admin.access',
                'dashboard.view',
                'campaigns.manage',
                'newsletter.manage',
                'hero-sliders.manage',
                'banners.manage',
                'homepage-sections.manage',
                'navigation-menus.manage',
                'pages.manage',
                'faqs.manage',
                'testimonials.manage',
                'store-locations.manage',
                'service-benefits.manage',
                'seo.manage',
            ],
            'Customer Support' => [
                'admin.access',
                'dashboard.view',
                'orders.view',
                'orders.update-status',
                'orders.print-invoice',
                'customers.view',
                'customer-notes.manage',
                'reviews.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($assignedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
