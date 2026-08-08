<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Cart\WishlistManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function store(Request $request, WishlistManager $wishlistManager): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $product = Product::query()->findOrFail((int) $validated['product_id']);
        $variant = filled($validated['product_variant_id'] ?? null)
            ? ProductVariant::query()->findOrFail((int) $validated['product_variant_id'])
            : null;

        $wishlistManager->add($product, $variant, $user);

        return back()->with('status', __('Saved to wishlist.'));
    }
}
