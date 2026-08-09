<?php

namespace App\Support\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdminDashboardMetrics
{
    private const int LowStockThreshold = 5;

    /**
     * @param  array{range?: string, start_date?: string|null, end_date?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $period = $this->period($filters);

        return [
            'filters' => $this->filterPayload($period),
            'metrics' => $this->metrics($period),
            'catalogSummary' => [
                __('Brands') => Brand::query()->count(),
                __('Categories') => Category::query()->count(),
                __('Collections') => ProductCollection::query()->count(),
            ],
            'orderStatusCounts' => $this->orderStatusCounts($period),
            'bestSellingProducts' => $this->bestSellingProducts($period),
            'topCategories' => $this->topCategories($period),
            'topBrands' => $this->topBrands($period),
            'couponUsage' => $this->couponUsage($period),
            'stockWatchlist' => $this->stockWatchlist(),
            'recentOrders' => $this->recentOrders(),
        ];
    }

    /**
     * @param  array{range?: string, start_date?: string|null, end_date?: string|null}  $filters
     * @return array{range: string, label: string, start: CarbonImmutable, end: CarbonImmutable, previous_start: CarbonImmutable, previous_end: CarbonImmutable}
     */
    protected function period(array $filters): array
    {
        $range = $filters['range'] ?? '30d';
        $range = in_array($range, ['7d', '30d', '90d', '12m', 'custom'], true) ? $range : '30d';

        if ($range === 'custom') {
            $startDate = $filters['start_date'] ?? CarbonImmutable::now()->toDateString();
            $endDate = $filters['end_date'] ?? CarbonImmutable::now()->toDateString();

            $start = CarbonImmutable::parse((string) $startDate)->startOfDay();
            $end = CarbonImmutable::parse((string) $endDate)->endOfDay();
            $label = $start->format('M j, Y').' - '.$end->format('M j, Y');
        } else {
            $end = CarbonImmutable::now()->endOfDay();
            [$start, $label] = match ($range) {
                '7d' => [$end->subDays(6)->startOfDay(), __('Last 7 days')],
                '90d' => [$end->subDays(89)->startOfDay(), __('Last 90 days')],
                '12m' => [$end->subYear()->addDay()->startOfDay(), __('Last 12 months')],
                default => [$end->subDays(29)->startOfDay(), __('Last 30 days')],
            };
        }

        $days = max(1, (int) floor($start->diffInDays($end)) + 1);
        $previousEnd = $start->subSecond();
        $previousStart = $start->subDays($days);

        return [
            'range' => $range,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @param  array{range: string, label: string, start: CarbonImmutable, end: CarbonImmutable, previous_start: CarbonImmutable, previous_end: CarbonImmutable}  $period
     * @return array{range: string, label: string, start_date: string, end_date: string, previous_label: string, range_options: array<string, string>}
     */
    protected function filterPayload(array $period): array
    {
        return [
            'range' => $period['range'],
            'label' => $period['label'],
            'start_date' => $period['start']->toDateString(),
            'end_date' => $period['end']->toDateString(),
            'previous_label' => $period['previous_start']->format('M j, Y').' - '.$period['previous_end']->format('M j, Y'),
            'range_options' => [
                '7d' => __('Last 7 days'),
                '30d' => __('Last 30 days'),
                '90d' => __('Last 90 days'),
                '12m' => __('Last 12 months'),
                'custom' => __('Custom'),
            ],
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, previous_start: CarbonImmutable, previous_end: CarbonImmutable}  $period
     * @return list<array{label: string, value: string, description: string, tone: string, trend: array{label: string, direction: string, description: string}|null}>
     */
    protected function metrics(array $period): array
    {
        $currentOrderCount = $this->ordersBetween($period['start'], $period['end'])->count();
        $previousOrderCount = $this->ordersBetween($period['previous_start'], $period['previous_end'])->count();
        $currentOrderTotal = (float) $this->ordersBetween($period['start'], $period['end'])->sum('grand_total');
        $previousOrderTotal = (float) $this->ordersBetween($period['previous_start'], $period['previous_end'])->sum('grand_total');
        $currentPaidRevenue = $this->paidRevenue($period['start'], $period['end']);
        $previousPaidRevenue = $this->paidRevenue($period['previous_start'], $period['previous_end']);
        $currentRefunds = $this->refundedAmount($period['start'], $period['end']);
        $previousRefunds = $this->refundedAmount($period['previous_start'], $period['previous_end']);
        $currentNetSales = max(0.0, $currentPaidRevenue - $currentRefunds);
        $previousNetSales = max(0.0, $previousPaidRevenue - $previousRefunds);
        $currentNewCustomers = $this->newCustomerCount($period['start'], $period['end']);
        $previousNewCustomers = $this->newCustomerCount($period['previous_start'], $period['previous_end']);
        $currentReturningCustomers = $this->returningCustomerCount($period['start'], $period['end']);
        $previousReturningCustomers = $this->returningCustomerCount($period['previous_start'], $period['previous_end']);
        $lowStock = $this->lowStockVariantCount();
        $outOfStock = $this->outOfStockVariantCount();

        return [
            $this->metric(__('Gross revenue'), $this->currency($currentPaidRevenue), __('Paid order grand totals in the selected period.'), 'teal', $currentPaidRevenue, $previousPaidRevenue),
            $this->metric(__('Net sales'), $this->currency($currentNetSales), __('Paid revenue minus completed refunds.'), 'teal', $currentNetSales, $previousNetSales),
            $this->metric(__('Orders'), number_format($currentOrderCount), __('Submitted orders in the selected period.'), 'neutral', $currentOrderCount, $previousOrderCount),
            $this->metric(__('Average order value'), $this->currency($currentOrderCount === 0 ? 0.0 : $currentOrderTotal / $currentOrderCount), __('Grand total divided by submitted orders.'), 'neutral', $currentOrderCount === 0 ? 0.0 : $currentOrderTotal / $currentOrderCount, $previousOrderCount === 0 ? 0.0 : $previousOrderTotal / $previousOrderCount),
            $this->metric(__('New customers'), number_format($currentNewCustomers), __('Roleless accounts created in this period.'), 'neutral', $currentNewCustomers, $previousNewCustomers),
            $this->metric(__('Returning customers'), number_format($currentReturningCustomers), __('Customers with an order in this period and an earlier order.'), 'teal', $currentReturningCustomers, $previousReturningCustomers),
            $this->metric(__('Pending orders'), number_format($this->statusCount(OrderStatus::Pending, $period['start'], $period['end'])), __('Orders waiting for confirmation.'), 'amber', $this->statusCount(OrderStatus::Pending, $period['start'], $period['end']), $this->statusCount(OrderStatus::Pending, $period['previous_start'], $period['previous_end'])),
            $this->metric(__('Processing orders'), number_format($this->statusCount(OrderStatus::Processing, $period['start'], $period['end'])), __('Orders being prepared for fulfillment.'), 'neutral', $this->statusCount(OrderStatus::Processing, $period['start'], $period['end']), $this->statusCount(OrderStatus::Processing, $period['previous_start'], $period['previous_end'])),
            $this->metric(__('Delivered orders'), number_format($this->statusCount(OrderStatus::Delivered, $period['start'], $period['end'])), __('Orders marked delivered.'), 'teal', $this->statusCount(OrderStatus::Delivered, $period['start'], $period['end']), $this->statusCount(OrderStatus::Delivered, $period['previous_start'], $period['previous_end'])),
            $this->metric(__('Cancelled orders'), number_format($this->statusCount(OrderStatus::Cancelled, $period['start'], $period['end'])), __('Orders cancelled in the period.'), 'rose', $this->statusCount(OrderStatus::Cancelled, $period['start'], $period['end']), $this->statusCount(OrderStatus::Cancelled, $period['previous_start'], $period['previous_end'])),
            $this->metric(__('Low-stock variants'), number_format($lowStock), __('Current active variants above zero and at or below threshold.'), 'amber'),
            $this->metric(__('Out-of-stock variants'), number_format($outOfStock), __('Current active variants with no available sellable stock.'), 'rose'),
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $period
     * @return list<array{status: string, label: string, count: int, tone: string}>
     */
    protected function orderStatusCounts(array $period): array
    {
        return array_map(fn (OrderStatus $status): array => [
            'status' => $status->value,
            'label' => str($status->value)->replace('_', ' ')->title()->toString(),
            'count' => $this->statusCount($status, $period['start'], $period['end']),
            'tone' => $this->statusTone($status),
        ], OrderStatus::cases());
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $period
     * @return list<array{name: string, sku: string, units: int, revenue: string}>
     */
    protected function bestSellingProducts(array $period): array
    {
        $products = OrderItem::query()
            ->select(['product_id', 'product_name', 'sku'])
            ->selectRaw('sum(quantity) as units_sold')
            ->selectRaw('sum(line_total) as revenue_total')
            ->whereHas('order', fn (Builder $query) => $query
                ->whereBetween('placed_at', [$period['start'], $period['end']])
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]))
            ->groupBy('product_id', 'product_name', 'sku')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => $item->product_name,
                'sku' => $item->sku ?: 'No SKU',
                'units' => (int) $item->getAttribute('units_sold'),
                'revenue' => $this->currency((float) $item->getAttribute('revenue_total')),
            ])
            ->values()
            ->all();

        return array_values($products);
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $period
     * @return list<array{name: string, units: int}>
     */
    protected function topCategories(array $period): array
    {
        $categories = Category::query()
            ->select(['categories.id', 'categories.name'])
            ->selectRaw('sum(order_items.quantity) as units_sold')
            ->join('category_product', 'categories.id', '=', 'category_product.category_id')
            ->join('order_items', 'category_product.product_id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.placed_at', [$period['start'], $period['end']])
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'units' => (int) $category->getAttribute('units_sold'),
            ])
            ->values()
            ->all();

        return array_values($categories);
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $period
     * @return list<array{name: string, units: int}>
     */
    protected function topBrands(array $period): array
    {
        $brands = Brand::query()
            ->select(['brands.id', 'brands.name'])
            ->selectRaw('sum(order_items.quantity) as units_sold')
            ->join('products', 'brands.id', '=', 'products.brand_id')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.placed_at', [$period['start'], $period['end']])
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->groupBy('brands.id', 'brands.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn (Brand $brand): array => [
                'name' => $brand->name,
                'units' => (int) $brand->getAttribute('units_sold'),
            ])
            ->values()
            ->all();

        return array_values($brands);
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $period
     * @return list<array{code: string, name: string, redemptions: int, discount: string}>
     */
    protected function couponUsage(array $period): array
    {
        $coupons = Coupon::query()
            ->select(['coupons.id', 'coupons.code', 'coupons.name'])
            ->selectRaw('count(coupon_redemptions.id) as redemption_count')
            ->selectRaw('sum(coupon_redemptions.discount_amount) as discount_total')
            ->join('coupon_redemptions', 'coupons.id', '=', 'coupon_redemptions.coupon_id')
            ->whereBetween('coupon_redemptions.redeemed_at', [$period['start'], $period['end']])
            ->groupBy('coupons.id', 'coupons.code', 'coupons.name')
            ->orderByDesc('redemption_count')
            ->limit(5)
            ->get()
            ->map(fn (Coupon $coupon): array => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'redemptions' => (int) $coupon->getAttribute('redemption_count'),
                'discount' => $this->currency((float) $coupon->getAttribute('discount_total')),
            ])
            ->values()
            ->all();

        return array_values($coupons);
    }

    /**
     * @return list<array{product: string, sku: string, available: int, threshold: int, tone: string}>
     */
    protected function stockWatchlist(): array
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= coalesce(low_stock_threshold, ?)', [self::LowStockThreshold])
            ->orderByRaw('(stock_quantity - reserved_quantity) asc')
            ->limit(6)
            ->get()
            ->map(function (ProductVariant $variant): array {
                $product = $variant->product;
                $available = $variant->availableQuantity();

                return [
                    'product' => $product instanceof Product ? $product->name : 'Unknown product',
                    'sku' => $variant->sku,
                    'available' => $available,
                    'threshold' => (int) ($variant->low_stock_threshold ?? self::LowStockThreshold),
                    'tone' => $available <= 0 ? 'rose' : 'amber',
                ];
            })
            ->values()
            ->all();

        return array_values($variants);
    }

    /**
     * @return Collection<int, Order>
     */
    protected function recentOrders(): Collection
    {
        return Order::query()
            ->with('user')
            ->latest('placed_at')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return Builder<Order>
     */
    protected function ordersBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Order::query()->whereBetween('placed_at', [$start, $end]);
    }

    protected function paidRevenue(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) $this->ordersBetween($start, $end)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum('grand_total');
    }

    protected function refundedAmount(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) Refund::query()
            ->where('status', RefundStatus::Refunded->value)
            ->whereBetween('refunded_at', [$start, $end])
            ->sum('amount');
    }

    protected function newCustomerCount(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    protected function returningCustomerCount(CarbonImmutable $start, CarbonImmutable $end): int
    {
        $currentCustomerIds = $this->ordersBetween($start, $end)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        if ($currentCustomerIds === []) {
            return 0;
        }

        return Order::query()
            ->whereIn('user_id', $currentCustomerIds)
            ->where('placed_at', '<', $start)
            ->distinct()
            ->count('user_id');
    }

    protected function statusCount(OrderStatus $status, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $this->ordersBetween($start, $end)
            ->where('status', $status->value)
            ->count();
    }

    protected function lowStockVariantCount(): int
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) > 0')
            ->whereRaw('(stock_quantity - reserved_quantity) <= coalesce(low_stock_threshold, ?)', [self::LowStockThreshold])
            ->count();
    }

    protected function outOfStockVariantCount(): int
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= 0')
            ->count();
    }

    protected function statusTone(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'amber',
            OrderStatus::Delivered => 'teal',
            OrderStatus::Cancelled, OrderStatus::Returned, OrderStatus::Refunded => 'rose',
            default => 'neutral',
        };
    }

    /**
     * @return array{label: string, value: string, description: string, tone: string, trend: array{label: string, direction: string, description: string}|null}
     */
    protected function metric(string $label, string $value, string $description, string $tone, int|float|null $current = null, int|float|null $previous = null): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'description' => $description,
            'tone' => $tone,
            'trend' => $current === null || $previous === null ? null : $this->trend((float) $current, (float) $previous),
        ];
    }

    /**
     * @return array{label: string, direction: string, description: string}
     */
    protected function trend(float $current, float $previous): array
    {
        if ($previous === 0.0 && $current === 0.0) {
            return [
                'label' => __('No change'),
                'direction' => 'neutral',
                'description' => __('vs previous period'),
            ];
        }

        if ($previous === 0.0) {
            return [
                'label' => __('New activity'),
                'direction' => 'up',
                'description' => __('vs previous period'),
            ];
        }

        $change = (($current - $previous) / abs($previous)) * 100;

        if (abs($change) < 0.05) {
            return [
                'label' => __('No change'),
                'direction' => 'neutral',
                'description' => __('vs previous period'),
            ];
        }

        return [
            'label' => ($change > 0 ? '+' : '').number_format($change, 1).'%',
            'direction' => $change > 0 ? 'up' : 'down',
            'description' => __('vs previous period'),
        ];
    }

    protected function currency(float $value): string
    {
        return 'BDT '.number_format($value, 2);
    }
}
