<?php

namespace App\Livewire\Storefront;

use App\Models\CartItem;
use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CartPage extends Component
{
    public function increment(int $cartItemId): void
    {
        $item = $this->cartItem($cartItemId);

        $this->updateQuantity($cartItemId, $item->quantity + 1);
    }

    public function decrement(int $cartItemId): void
    {
        $item = $this->cartItem($cartItemId);

        if ($item->quantity <= 1) {
            $this->remove($cartItemId);

            return;
        }

        $this->updateQuantity($cartItemId, $item->quantity - 1);
    }

    public function updateQuantity(int $cartItemId, mixed $quantity): void
    {
        try {
            app(CartManager::class)->updateItem($cartItemId, (int) $quantity);
        } catch (ValidationException $exception) {
            $this->addError('cart', $this->validationMessage($exception));
        }
    }

    public function remove(int $cartItemId): void
    {
        app(CartManager::class)->removeItem($cartItemId);
        session()->flash('status', __('Cart item removed.'));
    }

    public function clear(): void
    {
        app(CartManager::class)->clear();
        session()->flash('status', __('Cart cleared.'));
    }

    public function render(CartManager $cartManager): View
    {
        $cart = $cartManager->cartWithItems();

        return view('livewire.storefront.cart-page', [
            'cart' => $cart,
            'summary' => $cartManager->summary($cart),
        ])->layout('components.layouts.storefront', [
            'title' => __('Shopping cart'),
        ]);
    }

    protected function cartItem(int $cartItemId): CartItem
    {
        $cart = app(CartManager::class)->cartWithItems();

        if (! $cart) {
            abort(404);
        }

        $item = $cart->items->firstWhere('id', $cartItemId);

        if (! $item instanceof CartItem) {
            abort(404);
        }

        return $item;
    }

    protected function validationMessage(ValidationException $exception): string
    {
        return $exception->validator->errors()->first() ?: __('The cart could not be updated.');
    }
}
