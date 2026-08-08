<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $lowStockThreshold = 5;

        return view('admin.dashboard', [
            'metrics' => [
                [
                    'label' => __('Products'),
                    'value' => number_format(Product::query()->count()),
                    'description' => __('Total catalog records in the database.'),
                    'tone' => 'neutral',
                ],
                [
                    'label' => __('Published products'),
                    'value' => number_format(Product::query()->published()->count()),
                    'description' => __('Products currently eligible for storefront display.'),
                    'tone' => 'teal',
                ],
                [
                    'label' => __('Orders'),
                    'value' => number_format(Order::query()->count()),
                    'description' => __('All order records captured so far.'),
                    'tone' => 'neutral',
                ],
                [
                    'label' => __('Paid revenue'),
                    'value' => 'BDT '.number_format((float) Order::query()->where('payment_status', 'paid')->sum('grand_total'), 2),
                    'description' => __('Grand total from orders marked paid.'),
                    'tone' => 'teal',
                ],
                [
                    'label' => __('Customers'),
                    'value' => number_format(User::query()->whereDoesntHave('roles')->count()),
                    'description' => __('Registered users without admin roles.'),
                    'tone' => 'neutral',
                ],
                [
                    'label' => __('Low stock variants'),
                    'value' => number_format(ProductVariant::query()
                        ->where('is_active', true)
                        ->whereRaw('(stock_quantity - reserved_quantity) <= ?', [$lowStockThreshold])
                        ->count()),
                    'description' => __('Active variants at or below the local low-stock threshold.'),
                    'tone' => 'amber',
                ],
            ],
            'catalogSummary' => [
                __('Brands') => Brand::query()->count(),
                __('Categories') => Category::query()->count(),
                __('Collections') => ProductCollection::query()->count(),
            ],
            'recentOrders' => Order::query()
                ->with('user')
                ->latest('placed_at')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
