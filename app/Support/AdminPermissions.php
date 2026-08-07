<?php

namespace App\Support;

final class AdminPermissions
{
    public const SuperAdmin = 'Super Admin';

    public const Admin = 'Admin';

    public const ProductManager = 'Product Manager';

    public const OrderManager = 'Order Manager';

    public const ContentManager = 'Content Manager';

    public const CustomerSupport = 'Customer Support';

    public const AdminAccess = 'admin.access';

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => ['view'],
            'brands' => ['view', 'create', 'update', 'delete'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'collections' => ['view', 'create', 'update', 'delete'],
            'attributes' => ['view', 'create', 'update', 'delete'],
            'products' => ['view', 'create', 'update', 'delete'],
            'product-variants' => ['view', 'create', 'update', 'delete', 'manage'],
            'product-images' => ['view', 'create', 'update', 'delete', 'manage'],
            'inventory' => ['view', 'update'],
            'inventory-movements' => ['view', 'create'],
            'size-guides' => ['view', 'create', 'update', 'delete', 'manage'],
            'reviews' => ['view', 'moderate', 'delete'],
            'customers' => ['view', 'update'],
            'carts' => ['view', 'update'],
            'wishlists' => ['view'],
            'customer-notes' => ['manage'],
            'orders' => ['view', 'create', 'update-status', 'update-payment', 'cancel', 'refund', 'export', 'print-invoice'],
            'payments' => ['view', 'update', 'refund'],
            'shipments' => ['view', 'create', 'update', 'delete'],
            'returns' => ['view', 'create', 'update', 'delete'],
            'refunds' => ['view', 'create', 'update'],
            'coupons' => ['view', 'create', 'update', 'delete'],
            'coupon-redemptions' => ['view'],
            'campaigns' => ['view', 'create', 'update', 'delete', 'manage'],
            'newsletter' => ['view', 'update', 'delete', 'manage'],
            'announcement-bars' => ['view', 'create', 'update', 'delete', 'manage'],
            'hero-sliders' => ['view', 'create', 'update', 'delete', 'manage'],
            'banners' => ['view', 'create', 'update', 'delete', 'manage'],
            'homepage-sections' => ['view', 'create', 'update', 'delete', 'manage'],
            'navigation-menus' => ['view', 'create', 'update', 'delete', 'manage'],
            'pages' => ['view', 'create', 'update', 'delete', 'manage'],
            'faqs' => ['view', 'create', 'update', 'delete', 'manage'],
            'testimonials' => ['view', 'create', 'update', 'delete', 'manage'],
            'store-locations' => ['view', 'create', 'update', 'delete', 'manage'],
            'service-benefits' => ['view', 'create', 'update', 'delete', 'manage'],
            'footer-sections' => ['view', 'create', 'update', 'delete', 'manage'],
            'social-links' => ['view', 'create', 'update', 'delete', 'manage'],
            'settings' => ['view', 'update'],
            'shipping-settings' => ['view', 'update'],
            'payment-settings' => ['view', 'update'],
            'seo' => ['manage'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            [self::AdminAccess],
            ...array_map(
                fn (string $group, array $actions): array => self::permissionsForGroup($group, $actions),
                array_keys(self::groups()),
                self::groups(),
            ),
        )));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rolePermissions(): array
    {
        return [
            self::SuperAdmin => self::all(),
            self::Admin => self::without(self::all(), ['payment-settings.update']),
            self::ProductManager => self::withAdminAccess(array_merge(
                self::permissionsForGroup('dashboard', ['view']),
                self::permissionsForGroup('brands', ['view', 'create', 'update']),
                self::permissionsForGroup('categories', ['view', 'create', 'update']),
                self::permissionsForGroup('collections', ['view', 'create', 'update']),
                self::permissionsForGroup('attributes', ['view', 'create', 'update']),
                self::permissionsForGroup('products', ['view', 'create', 'update']),
                self::permissionsForGroup('product-variants', ['view', 'create', 'update', 'manage']),
                self::permissionsForGroup('product-images', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('inventory', ['view', 'update']),
                self::permissionsForGroup('inventory-movements', ['view', 'create']),
                self::permissionsForGroup('size-guides', ['view', 'create', 'update', 'manage']),
                self::permissionsForGroup('reviews', ['view', 'moderate']),
            )),
            self::OrderManager => self::withAdminAccess(array_merge(
                self::permissionsForGroup('dashboard', ['view']),
                self::permissionsForGroup('orders'),
                self::permissionsForGroup('payments', ['view', 'update', 'refund']),
                self::permissionsForGroup('shipments', ['view', 'create', 'update', 'delete']),
                self::permissionsForGroup('returns', ['view', 'create', 'update']),
                self::permissionsForGroup('refunds', ['view', 'create', 'update']),
                self::permissionsForGroup('customers', ['view']),
                self::permissionsForGroup('customer-notes', ['manage']),
                self::permissionsForGroup('inventory', ['view']),
            )),
            self::ContentManager => self::withAdminAccess(array_merge(
                self::permissionsForGroup('dashboard', ['view']),
                self::permissionsForGroup('campaigns', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('newsletter', ['view', 'update', 'delete', 'manage']),
                self::permissionsForGroup('announcement-bars', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('hero-sliders', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('banners', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('homepage-sections', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('navigation-menus', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('pages', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('faqs', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('testimonials', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('store-locations', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('service-benefits', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('footer-sections', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('social-links', ['view', 'create', 'update', 'delete', 'manage']),
                self::permissionsForGroup('seo', ['manage']),
            )),
            self::CustomerSupport => self::withAdminAccess(array_merge(
                self::permissionsForGroup('dashboard', ['view']),
                self::permissionsForGroup('orders', ['view', 'update-status', 'print-invoice']),
                self::permissionsForGroup('customers', ['view']),
                self::permissionsForGroup('customer-notes', ['manage']),
                self::permissionsForGroup('reviews', ['view']),
                self::permissionsForGroup('returns', ['view', 'create']),
            )),
        ];
    }

    public static function permission(string $group, string $action): string
    {
        return $group.'.'.$action;
    }

    /**
     * @param  list<string>|null  $actions
     * @return list<string>
     */
    public static function permissionsForGroup(string $group, ?array $actions = null): array
    {
        $actions ??= self::groups()[$group] ?? [];

        return array_map(
            fn (string $action): string => self::permission($group, $action),
            $actions,
        );
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private static function withAdminAccess(array $permissions): array
    {
        return array_values(array_unique(array_merge([self::AdminAccess], $permissions)));
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<string>  $excluded
     * @return list<string>
     */
    private static function without(array $permissions, array $excluded): array
    {
        return array_values(array_diff($permissions, $excluded));
    }
}
