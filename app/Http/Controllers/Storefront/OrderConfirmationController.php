<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;

class OrderConfirmationController extends Controller
{
    public function __invoke(Order $order): View
    {
        $user = auth()->user();
        $sessionOrderId = session('checkout.last_order_id');
        $allowedBySession = is_numeric($sessionOrderId) && (int) $sessionOrderId === $order->id;
        $allowedByUser = $user instanceof User && $order->user_id === $user->id;

        abort_unless($allowedBySession || $allowedByUser, 404);

        return view('storefront.checkout-thank-you', [
            'order' => $order->load(['addresses', 'items', 'payments', 'shippingMethod']),
        ]);
    }
}
