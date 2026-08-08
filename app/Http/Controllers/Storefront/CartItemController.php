<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Support\Cart\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function store(Request $request, CartManager $cartManager): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY_PER_ITEM],
        ]);

        $variant = ProductVariant::query()
            ->with(['product.images', 'attributeValues.productAttribute', 'images'])
            ->findOrFail((int) $validated['product_variant_id']);

        $cartManager->add($variant, (int) $validated['quantity']);

        return back()->with('status', __('Added to cart.'));
    }
}
