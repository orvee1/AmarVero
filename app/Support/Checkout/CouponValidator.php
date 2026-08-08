<?php

namespace App\Support\Checkout;

use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CouponValidator
{
    /**
     * @param  list<array{item: CartItem, unit_price: float, line_total: float}>  $lines
     * @return array{coupon: Coupon, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string}
     */
    public function applyCode(Cart $cart, string $code, array $lines, ?User $user = null, ?string $email = null): array
    {
        $coupon = $this->couponForCode($code);

        if (! $coupon instanceof Coupon) {
            throw ValidationException::withMessages([
                'couponCode' => __('That coupon code is not valid.'),
            ]);
        }

        $result = $this->discountForCart($coupon, $cart, $lines, $user, $email);

        $cart->forceFill([
            'coupon_id' => $coupon->id,
        ])->save();

        return $result;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->forceFill([
            'coupon_id' => null,
        ])->save();
    }

    /**
     * @param  list<array{item: CartItem, unit_price: float, line_total: float}>  $lines
     * @return array{coupon: Coupon, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string}
     */
    public function discountForCart(Coupon $coupon, Cart $cart, array $lines, ?User $user = null, ?string $email = null): array
    {
        $subtotal = $this->lineSubtotal($lines);

        $this->assertCouponIsUsable($coupon, $subtotal, $user, $email);

        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $lines);

        if ($eligibleSubtotal <= 0.0) {
            throw ValidationException::withMessages([
                'couponCode' => __('That coupon does not apply to the items in this cart.'),
            ]);
        }

        $discountTotal = $this->discountTotal($coupon, $eligibleSubtotal);

        return [
            'coupon' => $coupon,
            'discount_total' => $discountTotal,
            'free_shipping' => $this->discountType($coupon) === DiscountType::FreeShipping,
            'eligible_subtotal' => $eligibleSubtotal,
            'message' => __('Coupon :code applied.', ['code' => $coupon->code]),
        ];
    }

    protected function couponForCode(string $code): ?Coupon
    {
        $normalizedCode = strtoupper(trim($code));

        if ($normalizedCode === '') {
            return null;
        }

        return Coupon::query()
            ->whereRaw('upper(code) = ?', [$normalizedCode])
            ->first();
    }

    protected function assertCouponIsUsable(Coupon $coupon, float $subtotal, ?User $user, ?string $email): void
    {
        if (! $coupon->is_active) {
            $this->invalidCoupon(__('That coupon is no longer active.'));
        }

        if ($coupon->starts_at !== null && now()->lessThan($coupon->starts_at)) {
            $this->invalidCoupon(__('That coupon is not active yet.'));
        }

        if ($coupon->ends_at !== null && now()->greaterThan($coupon->ends_at)) {
            $this->invalidCoupon(__('That coupon has expired.'));
        }

        if ($coupon->minimum_order_amount !== null && $subtotal < (float) $coupon->minimum_order_amount) {
            $this->invalidCoupon(__('This coupon requires a minimum order of BDT :amount.', [
                'amount' => number_format((float) $coupon->minimum_order_amount, 2),
            ]));
        }

        if ($coupon->total_usage_limit !== null && (int) $coupon->usage_count >= (int) $coupon->total_usage_limit) {
            $this->invalidCoupon(__('That coupon has reached its usage limit.'));
        }

        if ($coupon->eligibleUsers()->exists() && (! $user instanceof User || ! $coupon->eligibleUsers()->whereKey($user->id)->exists())) {
            $this->invalidCoupon(__('That coupon is not available for this account.'));
        }

        $this->assertCustomerUsageLimit($coupon, $user, $email);
        $this->assertFirstOrderOnly($coupon, $user, $email);
    }

    protected function assertCustomerUsageLimit(Coupon $coupon, ?User $user, ?string $email): void
    {
        if ($coupon->per_customer_usage_limit === null) {
            return;
        }

        if (! $user instanceof User && ! filled($email)) {
            $this->invalidCoupon(__('Enter your email before applying this coupon.'));
        }

        $usageCount = $coupon->redemptions()
            ->when(
                $user instanceof User,
                fn (Builder $query): Builder => $query->where('user_id', $user->id),
                fn (Builder $query): Builder => $query->whereHas('order', fn (Builder $query): Builder => $query->where('email', (string) $email)),
            )
            ->count();

        if ($usageCount >= (int) $coupon->per_customer_usage_limit) {
            $this->invalidCoupon(__('You have already used this coupon.'));
        }
    }

    protected function assertFirstOrderOnly(Coupon $coupon, ?User $user, ?string $email): void
    {
        if (! $coupon->first_order_only) {
            return;
        }

        if (! $user instanceof User && ! filled($email)) {
            $this->invalidCoupon(__('Enter your email before applying this coupon.'));
        }

        $hasPreviousOrder = Order::query()
            ->where(function (Builder $query) use ($user, $email): void {
                if ($user instanceof User) {
                    $query->where('user_id', $user->id);
                }

                if (filled($email)) {
                    $method = $user instanceof User ? 'orWhere' : 'where';
                    $query->{$method}('email', (string) $email);
                }
            })
            ->exists();

        if ($hasPreviousOrder) {
            $this->invalidCoupon(__('That coupon is reserved for first orders.'));
        }
    }

    /**
     * @param  list<array{item: CartItem, unit_price: float, line_total: float}>  $lines
     */
    protected function lineSubtotal(array $lines): float
    {
        return round(array_sum(array_map(
            static fn (array $line): float => $line['line_total'],
            $lines,
        )), 2);
    }

    /**
     * @param  list<array{item: CartItem, unit_price: float, line_total: float}>  $lines
     */
    protected function eligibleSubtotal(Coupon $coupon, array $lines): float
    {
        $productIds = $this->relationIds($coupon, 'products', 'products.id');
        $brandIds = $this->relationIds($coupon, 'brands', 'brands.id');
        $categoryIds = $this->relationIds($coupon, 'categories', 'categories.id');
        $hasScopedRules = $productIds !== [] || $brandIds !== [] || $categoryIds !== [];

        if (! $hasScopedRules) {
            return $this->lineSubtotal($lines);
        }

        $subtotal = 0.0;

        foreach ($lines as $line) {
            $product = $line['item']->product;

            if (! $product instanceof Product) {
                continue;
            }

            $product->loadMissing('categories');

            $matchesProduct = in_array($product->id, $productIds, true);
            $matchesBrand = $product->brand_id !== null && in_array($product->brand_id, $brandIds, true);
            $matchesCategory = $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();

            if ($matchesProduct || $matchesBrand || $matchesCategory) {
                $subtotal += $line['line_total'];
            }
        }

        return round($subtotal, 2);
    }

    /**
     * @return list<int>
     */
    protected function relationIds(Coupon $coupon, string $relation, string $column): array
    {
        return $coupon->{$relation}()
            ->pluck($column)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    protected function discountTotal(Coupon $coupon, float $eligibleSubtotal): float
    {
        $discountType = $this->discountType($coupon);

        $discountTotal = match ($discountType) {
            DiscountType::Fixed => min((float) $coupon->value, $eligibleSubtotal),
            DiscountType::Percentage => $eligibleSubtotal * min(max((float) $coupon->value, 0.0), 100.0) / 100,
            DiscountType::FreeShipping => 0.0,
        };

        if ($coupon->maximum_discount_amount !== null) {
            $discountTotal = min($discountTotal, (float) $coupon->maximum_discount_amount);
        }

        return round(max(0.0, min($discountTotal, $eligibleSubtotal)), 2);
    }

    protected function discountType(Coupon $coupon): DiscountType
    {
        $discountType = $coupon->getAttribute('discount_type');

        if ($discountType instanceof DiscountType) {
            return $discountType;
        }

        return DiscountType::from((string) $discountType);
    }

    protected function invalidCoupon(string $message): never
    {
        throw ValidationException::withMessages([
            'couponCode' => $message,
        ]);
    }
}
