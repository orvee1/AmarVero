<?php

namespace App\Livewire\Storefront;

use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use App\Support\Cart\CartManager;
use App\Support\Cart\WishlistManager;
use App\Support\Storefront\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class WishlistPage extends Component
{
    public function remove(int $wishlistItemId): void
    {
        app(WishlistManager::class)->removeItem($wishlistItemId, $this->user());
        session()->flash('status', __('Wishlist item removed.'));
    }

    public function moveToCart(int $wishlistItemId): void
    {
        $wishlistManager = app(WishlistManager::class);
        $wishlistItem = $wishlistManager->itemForUser($wishlistItemId, $this->user());
        $variant = $this->variantForCart($wishlistItem);

        if (! $variant instanceof ProductVariant) {
            $this->addError('wishlist', __('Choose product options before moving this item to cart.'));

            return;
        }

        try {
            app(CartManager::class)->add($variant, 1);
            $wishlistManager->removeItem($wishlistItemId, $this->user());
            session()->flash('status', __('Moved to cart.'));
        } catch (ValidationException $exception) {
            $this->addError('wishlist', $exception->validator->errors()->first() ?: __('The wishlist item could not be moved.'));
        }
    }

    public function render(WishlistManager $wishlistManager, ProductCatalog $catalog): View
    {
        return view('livewire.storefront.wishlist-page', [
            'wishlist' => $wishlistManager->wishlistWithItems($this->user()),
            'catalog' => $catalog,
        ])->layout('components.layouts.storefront', [
            'title' => __('Wishlist'),
        ]);
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function variantForCart(WishlistItem $wishlistItem): ?ProductVariant
    {
        if ($wishlistItem->productVariant instanceof ProductVariant) {
            return $wishlistItem->productVariant;
        }

        $variants = $wishlistItem->product->variants->where('is_active', true)->values();

        return $variants->count() === 1 ? $variants->first() : null;
    }
}
