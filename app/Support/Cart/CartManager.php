<?php

namespace App\Support\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Storefront\ProductCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartManager
{
    public const int MAX_QUANTITY_PER_ITEM = 20;

    protected const string GUEST_CART_SESSION_KEY = 'storefront.cart_id';

    public function __construct(protected ProductCatalog $catalog)
    {
        //
    }

    public function currentCart(bool $create = true): ?Cart
    {
        $user = Auth::user();

        if ($user instanceof User) {
            return $this->activeUserCart($user, $create);
        }

        return $this->activeGuestCart($create);
    }

    public function cartItemCount(): int
    {
        $cart = $this->currentCart(false);

        if (! $cart instanceof Cart) {
            return 0;
        }

        return (int) $cart->items()->sum('quantity');
    }

    public function cartWithItems(?Cart $cart = null): ?Cart
    {
        $cart ??= $this->currentCart(false);

        if (! $cart instanceof Cart) {
            return null;
        }

        return $cart->load([
            'items.product.brand',
            'items.product.images',
            'items.productVariant.attributeValues.productAttribute',
            'items.productVariant.images',
        ]);
    }

    public function add(ProductVariant $variant, int $quantity = 1): CartItem
    {
        $cart = $this->currentCart();

        if (! $cart instanceof Cart) {
            throw ValidationException::withMessages([
                'cart' => __('A cart could not be started for this session.'),
            ]);
        }

        return DB::transaction(fn (): CartItem => $this->addToCart($cart, $variant, $quantity));
    }

    public function updateItem(int $cartItemId, int $quantity): CartItem
    {
        $cartItem = $this->cartItemForCurrentCart($cartItemId);
        $quantity = $this->normalizedQuantity($quantity);

        return DB::transaction(function () use ($cartItem, $quantity): CartItem {
            $variant = $cartItem->productVariant()->with(['product.images', 'attributeValues.productAttribute'])->firstOrFail();

            $this->ensureCanPurchase($variant, $quantity);

            $cartItem->forceFill([
                'quantity' => $quantity,
                'unit_price_snapshot' => $this->unitPrice($variant->product, $variant),
                'options' => $this->optionSnapshot($variant),
            ])->save();

            return $cartItem->refresh();
        });
    }

    public function removeItem(int $cartItemId): void
    {
        $this->cartItemForCurrentCart($cartItemId)->delete();
    }

    public function clear(): void
    {
        $cart = $this->currentCart(false);

        if ($cart instanceof Cart) {
            $cart->items()->delete();
        }
    }

    /**
     * @return array{subtotal: float, item_count: int, lines: list<array{item: CartItem, unit_price: float, line_total: float}>}
     */
    public function summary(?Cart $cart = null): array
    {
        $cart = $this->cartWithItems($cart);

        if (! $cart instanceof Cart) {
            return [
                'subtotal' => 0.0,
                'item_count' => 0,
                'lines' => [],
            ];
        }

        $lines = [];
        $subtotal = 0.0;
        $itemCount = 0;

        foreach ($cart->items as $item) {
            $unitPrice = (float) $item->unit_price_snapshot;
            $lineTotal = $unitPrice * $item->quantity;

            $lines[] = [
                'item' => $item,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
            $itemCount += $item->quantity;
        }

        return [
            'subtotal' => $subtotal,
            'item_count' => $itemCount,
            'lines' => $lines,
        ];
    }

    public function mergeGuestCartIntoUser(User $user): ?Cart
    {
        $guestCart = $this->guestCartFromSession();

        if (! $guestCart instanceof Cart) {
            $guestCart = Cart::query()
                ->whereNull('user_id')
                ->where('session_id', $this->sessionId())
                ->where('status', CartStatus::Active)
                ->with('items.productVariant.product.images', 'items.productVariant.attributeValues.productAttribute')
                ->latest()
                ->first();
        }

        if (! $guestCart instanceof Cart) {
            return $this->activeUserCart($user, true);
        }

        $userCart = $this->activeUserCart($user, true);

        if (! $userCart instanceof Cart) {
            return null;
        }

        $mergeIssues = [];

        DB::transaction(function () use ($guestCart, $userCart, &$mergeIssues): void {
            foreach ($guestCart->items as $guestItem) {
                try {
                    $this->addToCart($userCart, $guestItem->productVariant, $guestItem->quantity, true);
                } catch (ValidationException $exception) {
                    $mergeIssues[] = [
                        'cart_item_id' => $guestItem->id,
                        'message' => $exception->validator->errors()->first(),
                    ];
                }
            }

            $rawMeta = $guestCart->getAttribute('meta');
            $meta = is_array($rawMeta) ? $rawMeta : [];

            if ($mergeIssues !== []) {
                $meta['merge_issues'] = $mergeIssues;
            }

            $guestCart->forceFill([
                'status' => CartStatus::Merged,
                'meta' => $meta,
            ])->save();
        });

        session()->forget(self::GUEST_CART_SESSION_KEY);

        return $this->cartWithItems($userCart->refresh());
    }

    protected function activeUserCart(User $user, bool $create): ?Cart
    {
        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::Active)
            ->latest()
            ->first();

        if ($cart instanceof Cart || ! $create) {
            return $cart;
        }

        return Cart::query()->create([
            'user_id' => $user->id,
            'status' => CartStatus::Active,
            'currency_code' => 'BDT',
        ]);
    }

    protected function activeGuestCart(bool $create): ?Cart
    {
        $sessionId = $this->sessionId();
        $cart = $this->guestCartFromSession();

        if (! $cart instanceof Cart) {
            $cart = Cart::query()
                ->whereNull('user_id')
                ->where('session_id', $sessionId)
                ->where('status', CartStatus::Active)
                ->latest()
                ->first();
        }

        if ($cart instanceof Cart || ! $create) {
            if ($cart instanceof Cart) {
                session()->put(self::GUEST_CART_SESSION_KEY, $cart->id);
            }

            return $cart;
        }

        $cart = Cart::query()->create([
            'session_id' => $sessionId,
            'status' => CartStatus::Active,
            'currency_code' => 'BDT',
            'expires_at' => now()->addDays(30),
        ]);

        session()->put(self::GUEST_CART_SESSION_KEY, $cart->id);

        return $cart;
    }

    protected function addToCart(Cart $cart, ProductVariant $variant, int $quantity, bool $capToAvailable = false): CartItem
    {
        $variant->loadMissing(['product.images', 'attributeValues.productAttribute']);

        $quantity = $this->normalizedQuantity($quantity);
        $existingItem = $cart->items()->where('product_variant_id', $variant->id)->first();
        $targetQuantity = $quantity;

        if ($existingItem instanceof CartItem) {
            $targetQuantity += $existingItem->quantity;
        }

        if ($capToAvailable) {
            $targetQuantity = min($targetQuantity, $this->maximumAllowedQuantity($variant));
        }

        $this->ensureCanPurchase($variant, $targetQuantity);

        $values = [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => $targetQuantity,
            'unit_price_snapshot' => $this->unitPrice($variant->product, $variant),
            'options' => $this->optionSnapshot($variant),
        ];

        if ($existingItem instanceof CartItem) {
            $existingItem->forceFill($values)->save();

            return $existingItem->refresh();
        }

        return $cart->items()->create($values);
    }

    protected function cartItemForCurrentCart(int $cartItemId): CartItem
    {
        $cart = $this->currentCart(false);

        if (! $cart instanceof Cart) {
            abort(404);
        }

        return $cart->items()->whereKey($cartItemId)->firstOrFail();
    }

    protected function ensureCanPurchase(ProductVariant $variant, int $quantity): void
    {
        $variant->loadMissing('product');
        $product = $variant->product;

        if (! $variant->is_active || ! $this->publishedProduct($product)) {
            throw ValidationException::withMessages([
                'product_variant_id' => __('This product is not available.'),
            ]);
        }

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw ValidationException::withMessages([
                'quantity' => __('Choose a quantity between 1 and :max.', ['max' => self::MAX_QUANTITY_PER_ITEM]),
            ]);
        }

        if (! $this->allowsBackorder($product, $variant) && $quantity > $variant->availableQuantity()) {
            throw ValidationException::withMessages([
                'quantity' => __('Only :count units are available.', ['count' => $variant->availableQuantity()]),
            ]);
        }
    }

    protected function normalizedQuantity(int $quantity): int
    {
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw ValidationException::withMessages([
                'quantity' => __('Choose a quantity between 1 and :max.', ['max' => self::MAX_QUANTITY_PER_ITEM]),
            ]);
        }

        return $quantity;
    }

    protected function maximumAllowedQuantity(ProductVariant $variant): int
    {
        $variant->loadMissing('product');

        if ($this->allowsBackorder($variant->product, $variant)) {
            return self::MAX_QUANTITY_PER_ITEM;
        }

        return min(self::MAX_QUANTITY_PER_ITEM, $variant->availableQuantity());
    }

    protected function publishedProduct(Product $product): bool
    {
        return Product::query()->published()->whereKey($product->id)->exists();
    }

    protected function allowsBackorder(Product $product, ProductVariant $variant): bool
    {
        return (bool) $variant->allow_backorder || (bool) $product->allow_backorder;
    }

    protected function unitPrice(Product $product, ProductVariant $variant): float
    {
        if ($variant->price_override !== null) {
            return (float) $variant->price_override;
        }

        return (float) ($this->catalog->effectivePrice($product) ?? 0);
    }

    /**
     * @return array{product_name: string, product_slug: string, variant_label: string|null, sku: string, image_url: string|null, attributes: list<array{attribute: string, value: string}>}
     */
    protected function optionSnapshot(ProductVariant $variant): array
    {
        $product = $variant->product;
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

    protected function sessionId(): string
    {
        return session()->getId();
    }

    protected function guestCartFromSession(): ?Cart
    {
        $cartId = session()->get(self::GUEST_CART_SESSION_KEY);

        if (! is_numeric($cartId)) {
            return null;
        }

        return Cart::query()
            ->whereNull('user_id')
            ->where('status', CartStatus::Active)
            ->whereKey((int) $cartId)
            ->with('items.productVariant.product.images', 'items.productVariant.attributeValues.productAttribute')
            ->first();
    }
}
