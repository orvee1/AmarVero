<?php

namespace App\Support\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WishlistManager
{
    public function defaultWishlist(?User $user = null, bool $create = true): ?Wishlist
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $wishlist = Wishlist::query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->latest()
            ->first();

        if ($wishlist instanceof Wishlist || ! $create) {
            return $wishlist;
        }

        return Wishlist::query()->create([
            'user_id' => $user->id,
            'name' => __('Wishlist'),
            'is_default' => true,
        ]);
    }

    public function wishlistWithItems(?User $user = null): ?Wishlist
    {
        $wishlist = $this->defaultWishlist($user, false);

        if (! $wishlist instanceof Wishlist) {
            return null;
        }

        return $wishlist->load([
            'items.product.brand',
            'items.product.images',
            'items.product.variants',
            'items.productVariant.attributeValues.productAttribute',
            'items.productVariant.images',
        ]);
    }

    public function itemCount(?User $user = null): int
    {
        $wishlist = $this->defaultWishlist($user, false);

        if (! $wishlist instanceof Wishlist) {
            return 0;
        }

        return $wishlist->items()->count();
    }

    public function add(Product $product, ?ProductVariant $variant = null, ?User $user = null): WishlistItem
    {
        $wishlist = $this->defaultWishlist($user);

        if (! $wishlist instanceof Wishlist) {
            throw ValidationException::withMessages([
                'wishlist' => __('Sign in to save products to a wishlist.'),
            ]);
        }

        $this->ensureCanSave($product, $variant);

        return WishlistItem::query()->firstOrCreate([
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
    }

    public function removeItem(int $wishlistItemId, ?User $user = null): void
    {
        $this->itemForUser($wishlistItemId, $user)->delete();
    }

    public function itemForUser(int $wishlistItemId, ?User $user = null): WishlistItem
    {
        $wishlist = $this->defaultWishlist($user, false);

        if (! $wishlist instanceof Wishlist) {
            abort(404);
        }

        return $wishlist->items()
            ->with('productVariant.product')
            ->whereKey($wishlistItemId)
            ->firstOrFail();
    }

    protected function ensureCanSave(Product $product, ?ProductVariant $variant): void
    {
        if (! Product::query()->published()->whereKey($product->id)->exists()) {
            throw ValidationException::withMessages([
                'product_id' => __('This product is not available.'),
            ]);
        }

        if ($variant === null) {
            return;
        }

        if ($variant->product_id !== $product->id || ! $variant->is_active) {
            throw ValidationException::withMessages([
                'product_variant_id' => __('This product option is not available.'),
            ]);
        }
    }
}
