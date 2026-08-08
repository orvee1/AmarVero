<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('dashboard', [
            'accountStats' => [
                [
                    'label' => __('Orders'),
                    'value' => number_format($user->orders()->count()),
                    'description' => __('Orders linked to your account.'),
                    'tone' => 'neutral',
                ],
                [
                    'label' => __('Addresses'),
                    'value' => number_format($user->addresses()->count()),
                    'description' => __('Saved delivery destinations.'),
                    'tone' => 'teal',
                ],
                [
                    'label' => __('Wishlists'),
                    'value' => number_format($user->wishlists()->count()),
                    'description' => __('Saved product lists.'),
                    'tone' => 'neutral',
                ],
                [
                    'label' => __('Reviews'),
                    'value' => number_format($user->productReviews()->count()),
                    'description' => __('Product feedback submitted from this account.'),
                    'tone' => 'amber',
                ],
            ],
            'latestOrder' => $user->orders()
                ->latest('placed_at')
                ->latest('id')
                ->first(),
            'recentOrders' => $user->orders()
                ->with(['items', 'shippingMethod'])
                ->latest('placed_at')
                ->latest('id')
                ->limit(3)
                ->get(),
            'defaultShippingAddress' => $user->addresses()
                ->where('is_default_shipping', true)
                ->latest()
                ->first(),
            'pendingReviewCount' => $user->productReviews()
                ->where('status', ReviewStatus::Pending)
                ->count(),
        ]);
    }
}
