<?php

namespace App\Support\Checkout;

use App\Enums\AddressType;
use App\Enums\CartStatus;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\Cart\CartManager;
use App\Support\Orders\OrderNotificationManager;
use App\Support\Storefront\ProductCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutManager
{
    public function __construct(
        protected CartManager $cartManager,
        protected CouponValidator $couponValidator,
        protected ShippingRateResolver $shippingRateResolver,
        protected ProductCatalog $catalog,
    ) {
        //
    }

    /**
     * @return array<string, string>
     */
    public function supportedPaymentMethods(): array
    {
        return [
            PaymentMethod::CashOnDelivery->value => __('Cash on delivery'),
            PaymentMethod::BankTransfer->value => __('Bank transfer'),
            PaymentMethod::Manual->value => __('Manual payment'),
        ];
    }

    /**
     * @param  array{item: CartItem, unit_price: float, line_total: float}  $line
     * @return array{item: CartItem, unit_price: float, line_total: float}
     */
    public function couponLine(array $line): array
    {
        return [
            'item' => $line['item'],
            'unit_price' => $line['unit_price'],
            'line_total' => $line['line_total'],
        ];
    }

    /**
     * @return array{
     *     cart: Cart|null,
     *     lines: list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>,
     *     subtotal: float,
     *     discount_total: float,
     *     tax_total: float,
     *     shipping_total: float,
     *     shipping_discount_total: float,
     *     grand_total: float,
     *     coupon_result: array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null},
     *     shipping_method: ShippingMethod|null
     * }
     */
    public function currentPreview(?int $shippingMethodId = null, string $countryCode = 'BD', ?string $region = null, ?User $user = null, ?string $email = null): array
    {
        $cart = $this->cartManager->currentCart(false);

        if (! $cart instanceof Cart) {
            return $this->emptyPreview();
        }

        return $this->preview($cart, $shippingMethodId, $countryCode, $region, $user, $email);
    }

    /**
     * @return array{
     *     cart: Cart|null,
     *     lines: list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>,
     *     subtotal: float,
     *     discount_total: float,
     *     tax_total: float,
     *     shipping_total: float,
     *     shipping_discount_total: float,
     *     grand_total: float,
     *     coupon_result: array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null},
     *     shipping_method: ShippingMethod|null
     * }
     */
    public function preview(
        Cart $cart,
        ?int $shippingMethodId = null,
        string $countryCode = 'BD',
        ?string $region = null,
        ?User $user = null,
        ?string $email = null,
        bool $refreshSnapshots = false,
        bool $lockVariants = false,
    ): array {
        $cart = $this->cartWithCheckoutRelations($cart);
        $lines = $this->lineSummaries($cart, $refreshSnapshots, $lockVariants);

        if ($lines === []) {
            return $this->emptyPreview($cart);
        }

        $subtotal = round(array_sum(array_map(
            static fn (array $line): float => $line['line_total'],
            $lines,
        )), 2);
        $couponResult = $this->couponResult($cart, $lines, $user, $email);
        $shippingResult = null;

        if ($shippingMethodId !== null) {
            $shippingResult = $this->shippingRateResolver->resolve(
                $shippingMethodId,
                $countryCode,
                $region,
                $subtotal,
                $couponResult['free_shipping'],
            );
        }

        $baseShippingTotal = $shippingResult === null ? 0.0 : $shippingResult['base_rate'];
        $shippingTotal = $shippingResult === null ? 0.0 : $shippingResult['rate'];
        $shippingDiscountTotal = $shippingResult === null ? 0.0 : max(0.0, $baseShippingTotal - $shippingTotal);
        $discountTotal = $couponResult['discount_total'];
        $taxTotal = 0.0;
        $grandTotal = round(max(0.0, $subtotal - $discountTotal + $taxTotal + $shippingTotal), 2);

        return [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'shipping_total' => round($shippingTotal, 2),
            'shipping_discount_total' => round($shippingDiscountTotal, 2),
            'grand_total' => $grandTotal,
            'coupon_result' => $couponResult,
            'shipping_method' => $shippingResult === null ? null : $shippingResult['method'],
        ];
    }

    /**
     * @return array{coupon: Coupon, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string}
     */
    public function applyCouponCode(string $code, ?User $user = null, ?string $email = null): array
    {
        $cart = $this->activeCart();
        $cart = $this->cartWithCheckoutRelations($cart);
        $lines = array_map(fn (array $line): array => $this->couponLine($line), $this->lineSummaries($cart));

        if ($lines === []) {
            throw ValidationException::withMessages([
                'couponCode' => __('Add an item to your cart before applying a coupon.'),
            ]);
        }

        return $this->couponValidator->applyCode($cart, $code, $lines, $user, $email);
    }

    public function clearCoupon(): void
    {
        $cart = $this->cartManager->currentCart(false);

        if ($cart instanceof Cart) {
            $this->couponValidator->removeCoupon($cart);
        }
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     email: string,
     *     phone?: string|null,
     *     line_one: string,
     *     line_two?: string|null,
     *     area?: string|null,
     *     city: string,
     *     region?: string|null,
     *     postal_code?: string|null,
     *     country_code: string,
     *     shipping_method_id: int,
     *     payment_method: string,
     *     customer_note?: string|null
     * }  $checkout
     */
    public function placeOrder(array $checkout, ?User $user = null): Order
    {
        $paymentMethod = $this->paymentMethodFrom((string) $checkout['payment_method']);

        $order = DB::transaction(function () use ($checkout, $paymentMethod, $user): Order {
            $currentCart = $this->activeCart();
            $cart = Cart::query()
                ->whereKey($currentCart->id)
                ->where('status', CartStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $cart instanceof Cart) {
                throw ValidationException::withMessages([
                    'cart' => __('Your cart is no longer available for checkout.'),
                ]);
            }

            $preview = $this->preview(
                $cart,
                (int) $checkout['shipping_method_id'],
                strtoupper((string) $checkout['country_code']),
                $checkout['region'] ?? null,
                $user,
                (string) $checkout['email'],
                true,
                true,
            );

            if ($preview['lines'] === []) {
                throw ValidationException::withMessages([
                    'cart' => __('Add an item to your cart before checkout.'),
                ]);
            }

            $order = $this->createOrder($checkout, $preview, $paymentMethod, $user);
            $this->createOrderAddresses($order, $checkout);
            $this->createOrderItems($order, $preview, $user);
            $this->createPayment($order, $paymentMethod, $user);
            $this->recordCouponRedemption($order, $preview, $user);
            $this->convertCart($cart, $order);

            return $order->load(['addresses', 'items', 'payments.events', 'statusEvents', 'shippingMethod']);
        });

        app(OrderNotificationManager::class)->sendOrderConfirmation($order);

        return $order;
    }

    protected function activeCart(): Cart
    {
        $cart = $this->cartManager->currentCart(false);

        if (! $cart instanceof Cart) {
            throw ValidationException::withMessages([
                'cart' => __('Add an item to your cart before checkout.'),
            ]);
        }

        return $cart;
    }

    protected function cartWithCheckoutRelations(Cart $cart): Cart
    {
        return $cart->load([
            'coupon',
            'items.product.brand',
            'items.product.categories',
            'items.product.images',
            'items.productVariant.attributeValues.productAttribute',
            'items.productVariant.images',
        ]);
    }

    /**
     * @return list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>
     */
    protected function lineSummaries(Cart $cart, bool $refreshSnapshots = false, bool $lockVariants = false): array
    {
        $lines = [];

        foreach ($cart->items as $item) {
            $variant = ProductVariant::query()
                ->with([
                    'product.brand',
                    'product.categories',
                    'product.images',
                    'attributeValues.productAttribute',
                    'images',
                ])
                ->when($lockVariants, fn ($query) => $query->lockForUpdate())
                ->whereKey($item->product_variant_id)
                ->first();

            if (! $variant instanceof ProductVariant) {
                throw ValidationException::withMessages([
                    'cart' => __('One of the cart items is no longer available.'),
                ]);
            }

            $this->ensureLineCanCheckout($variant, $item);

            $unitPrice = $this->unitPrice($variant);

            if ($refreshSnapshots) {
                $item->forceFill([
                    'unit_price_snapshot' => $unitPrice,
                    'options' => $this->optionSnapshot($variant, $item),
                ])->save();
            }

            $item->setRelation('product', $variant->product);
            $item->setRelation('productVariant', $variant);

            $lines[] = [
                'item' => $item,
                'variant' => $variant,
                'unit_price' => $unitPrice,
                'quantity' => $item->quantity,
                'line_total' => round($unitPrice * $item->quantity, 2),
            ];
        }

        return $lines;
    }

    protected function ensureLineCanCheckout(ProductVariant $variant, CartItem $item): void
    {
        $product = $variant->product;

        if (! $product instanceof Product || ! $variant->is_active || ! Product::query()->published()->whereKey($product->id)->exists()) {
            throw ValidationException::withMessages([
                'cart' => __('One of the cart items is no longer available.'),
            ]);
        }

        if ($item->quantity < 1 || $item->quantity > CartManager::MAX_QUANTITY_PER_ITEM) {
            throw ValidationException::withMessages([
                'cart' => __('One of the cart quantities is outside the allowed range.'),
            ]);
        }

        if (! $this->allowsBackorder($product, $variant) && $item->quantity > $variant->availableQuantity()) {
            throw ValidationException::withMessages([
                'cart' => __('Only :count units of :name are available.', [
                    'count' => $variant->availableQuantity(),
                    'name' => $product->name,
                ]),
            ]);
        }
    }

    /**
     * @param  list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>  $lines
     * @return array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null}
     */
    protected function couponResult(Cart $cart, array $lines, ?User $user, ?string $email): array
    {
        if (! $cart->coupon instanceof Coupon) {
            return $this->emptyCouponResult();
        }

        return $this->couponValidator->discountForCart(
            $cart->coupon,
            $cart,
            array_map(fn (array $line): array => $this->couponLine($line), $lines),
            $user,
            $email,
        );
    }

    protected function unitPrice(ProductVariant $variant): float
    {
        if ($variant->price_override !== null) {
            return round((float) $variant->price_override, 2);
        }

        $product = $variant->product;

        if (! $product instanceof Product) {
            return 0.0;
        }

        return round((float) ($this->catalog->effectivePrice($product) ?? 0.0), 2);
    }

    protected function allowsBackorder(Product $product, ProductVariant $variant): bool
    {
        return (bool) $variant->allow_backorder || (bool) $product->allow_backorder;
    }

    /**
     * @return array{product_name: string, product_slug: string, variant_label: string|null, sku: string, image_url: string|null, attributes: list<array{attribute: string, value: string}>}
     */
    protected function optionSnapshot(ProductVariant $variant, CartItem $item): array
    {
        $product = $variant->product;
        $existingOptions = $this->cartItemOptions($item);

        if (! $product instanceof Product) {
            return [
                'product_name' => $this->stringOption($existingOptions, 'product_name', '') ?? '',
                'product_slug' => $this->stringOption($existingOptions, 'product_slug', '') ?? '',
                'variant_label' => $this->stringOption($existingOptions, 'variant_label'),
                'sku' => $this->stringOption($existingOptions, 'sku', $variant->sku) ?? $variant->sku,
                'image_url' => $this->stringOption($existingOptions, 'image_url'),
                'attributes' => [],
            ];
        }

        $image = $variant->images->first() ?: $product->images->first();
        $attributes = [];

        foreach ($variant->attributeValues as $value) {
            $attributes[] = [
                'attribute' => $value->productAttribute->name,
                'value' => $value->display_value ?: $value->value,
            ];
        }

        return [
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'variant_label' => $variant->option_label,
            'sku' => $variant->sku,
            'image_url' => $image instanceof ProductImage ? $this->catalog->mediaUrl($image->path, $image->disk) : null,
            'attributes' => $attributes,
        ];
    }

    protected function paymentMethodFrom(string $value): PaymentMethod
    {
        $method = PaymentMethod::tryFrom($value);

        if (! $method instanceof PaymentMethod || ! array_key_exists($method->value, $this->supportedPaymentMethods())) {
            throw ValidationException::withMessages([
                'paymentMethod' => __('Choose an available payment method.'),
            ]);
        }

        return $method;
    }

    /**
     * @param  array<string, mixed>  $checkout
     * @param  array{
     *     subtotal: float,
     *     discount_total: float,
     *     tax_total: float,
     *     shipping_total: float,
     *     grand_total: float,
     *     coupon_result: array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null},
     *     shipping_method: ShippingMethod|null
     * }  $preview
     */
    protected function createOrder(array $checkout, array $preview, PaymentMethod $paymentMethod, ?User $user): Order
    {
        return Order::query()->create([
            'user_id' => $user?->id,
            'coupon_id' => $preview['coupon_result']['coupon']?->id,
            'shipping_method_id' => $preview['shipping_method']?->id,
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => (string) $checkout['customer_name'],
            'email' => strtolower((string) $checkout['email']),
            'phone' => $checkout['phone'] ?? null,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency_code' => 'BDT',
            'subtotal' => $preview['subtotal'],
            'discount_total' => $preview['discount_total'],
            'tax_total' => $preview['tax_total'],
            'shipping_total' => $preview['shipping_total'],
            'grand_total' => $preview['grand_total'],
            'customer_note' => $checkout['customer_note'] ?? null,
            'meta' => [
                'payment_method' => $paymentMethod->value,
                'coupon_code' => $preview['coupon_result']['coupon']?->code,
                'shipping_method_name' => $preview['shipping_method']?->name,
            ],
            'placed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $checkout
     */
    protected function createOrderAddresses(Order $order, array $checkout): void
    {
        foreach ([AddressType::Shipping, AddressType::Billing] as $type) {
            $order->addresses()->create([
                'type' => $type,
                'name' => (string) $checkout['customer_name'],
                'phone' => (string) ($checkout['phone'] ?? ''),
                'line_one' => (string) $checkout['line_one'],
                'line_two' => $checkout['line_two'] ?? null,
                'area' => $checkout['area'] ?? null,
                'city' => (string) $checkout['city'],
                'region' => $checkout['region'] ?? null,
                'postal_code' => $checkout['postal_code'] ?? null,
                'country_code' => strtoupper((string) $checkout['country_code']),
            ]);
        }
    }

    /**
     * @param  array{lines: list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>}  $preview
     */
    protected function createOrderItems(Order $order, array $preview, ?User $user): void
    {
        foreach ($preview['lines'] as $line) {
            $item = $line['item'];
            $variant = $line['variant'];
            $product = $variant->product;
            $options = $this->cartItemOptions($item);

            $order->items()->create([
                'product_id' => $product instanceof Product ? $product->id : null,
                'product_variant_id' => $variant->id,
                'product_name' => $this->stringOption($options, 'product_name', $product instanceof Product ? $product->name : __('Product')) ?? __('Product'),
                'variant_name' => $this->stringOption($options, 'variant_label', $variant->option_label),
                'sku' => $this->stringOption($options, 'sku', $variant->sku) ?? $variant->sku,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_total' => 0,
                'tax_total' => 0,
                'line_total' => $line['line_total'],
                'options' => $options,
            ]);

            $this->deductInventory($variant, $line['quantity'], $order, $user);
        }
    }

    protected function createPayment(Order $order, PaymentMethod $paymentMethod, ?User $user): Payment
    {
        $payment = $order->payments()->create([
            'method' => $paymentMethod,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'provider' => 'offline',
            'provider_payload' => [
                'instruction' => __('Payment will be confirmed by the operations team.'),
            ],
        ]);

        $payment->events()->create([
            'user_id' => $user?->id,
            'from_status' => null,
            'to_status' => PaymentStatus::Pending,
            'note' => __('Payment record created at checkout.'),
        ]);

        $order->statusEvents()->create([
            'user_id' => $user?->id,
            'from_status' => null,
            'to_status' => OrderStatus::Pending,
            'note' => __('Order placed by customer checkout.'),
        ]);

        return $payment;
    }

    /**
     * @param  array{
     *     discount_total: float,
     *     shipping_discount_total: float,
     *     coupon_result: array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null}
     * }  $preview
     */
    protected function recordCouponRedemption(Order $order, array $preview, ?User $user): void
    {
        $coupon = $preview['coupon_result']['coupon'];

        if (! $coupon instanceof Coupon) {
            return;
        }

        $discountAmount = round($preview['discount_total'] + $preview['shipping_discount_total'], 2);

        $coupon->redemptions()->create([
            'user_id' => $user?->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
            'redeemed_at' => now(),
        ]);

        Coupon::query()
            ->whereKey($coupon->id)
            ->increment('usage_count');
    }

    protected function deductInventory(ProductVariant $variant, int $quantity, Order $order, ?User $user): void
    {
        $product = $variant->product;

        if (! $product instanceof Product || ! $product->track_inventory) {
            return;
        }

        $deductibleQuantity = min($quantity, $variant->stock_quantity);

        if ($deductibleQuantity <= 0) {
            return;
        }

        $nextStockQuantity = $variant->stock_quantity - $deductibleQuantity;

        $variant->forceFill([
            'stock_quantity' => $nextStockQuantity,
        ])->save();

        InventoryMovement::query()->create([
            'product_variant_id' => $variant->id,
            'user_id' => $user?->id,
            'type' => InventoryMovementType::Sale,
            'quantity' => -$deductibleQuantity,
            'balance_after' => $nextStockQuantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => __('Checkout sale'),
        ]);
    }

    protected function convertCart(Cart $cart, Order $order): void
    {
        $rawMeta = $cart->getAttribute('meta');
        $meta = is_array($rawMeta) ? $rawMeta : [];
        $meta['converted_order_id'] = $order->id;
        $meta['converted_at'] = now()->toISOString();

        $cart->forceFill([
            'status' => CartStatus::Converted,
            'meta' => $meta,
        ])->save();
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'AV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * @return array<string, mixed>
     */
    protected function cartItemOptions(CartItem $item): array
    {
        $options = $item->getAttribute('options');

        return is_array($options) ? $options : [];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function stringOption(array $options, string $key, ?string $default = null): ?string
    {
        $value = $options[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array{
     *     cart: Cart|null,
     *     lines: list<array{item: CartItem, variant: ProductVariant, unit_price: float, quantity: int, line_total: float}>,
     *     subtotal: float,
     *     discount_total: float,
     *     tax_total: float,
     *     shipping_total: float,
     *     shipping_discount_total: float,
     *     grand_total: float,
     *     coupon_result: array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null},
     *     shipping_method: ShippingMethod|null
     * }
     */
    public function emptyPreview(?Cart $cart = null): array
    {
        return [
            'cart' => $cart,
            'lines' => [],
            'subtotal' => 0.0,
            'discount_total' => 0.0,
            'tax_total' => 0.0,
            'shipping_total' => 0.0,
            'shipping_discount_total' => 0.0,
            'grand_total' => 0.0,
            'coupon_result' => $this->emptyCouponResult(),
            'shipping_method' => null,
        ];
    }

    /**
     * @return array{coupon: Coupon|null, discount_total: float, free_shipping: bool, eligible_subtotal: float, message: string|null}
     */
    protected function emptyCouponResult(): array
    {
        return [
            'coupon' => null,
            'discount_total' => 0.0,
            'free_shipping' => false,
            'eligible_subtotal' => 0.0,
            'message' => null,
        ];
    }
}
