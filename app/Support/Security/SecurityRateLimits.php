<?php

namespace App\Support\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityRateLimits
{
    public const StorefrontSearch = 'storefront-search';

    public const CartWrites = 'cart-writes';

    public const WishlistWrites = 'wishlist-writes';

    public const Checkout = 'checkout';

    public const AdminRequests = 'admin-requests';

    public const CouponAttempts = 'coupon-attempts';

    public const ReviewSubmissions = 'review-submissions';

    public const int CouponMaxAttempts = 8;

    public const int CouponDecaySeconds = 300;

    public const int ReviewMaxAttempts = 5;

    public const int ReviewDecaySeconds = 600;

    public static function requestKey(Request $request, string $scope): string
    {
        return $scope.'|'.self::actorKey($request).'|ip:'.$request->ip();
    }

    public static function couponKey(?User $user, ?string $email = null): string
    {
        $emailPart = trim(Str::lower((string) $email));
        $emailPart = $emailPart === '' ? 'no-email' : hash('sha256', $emailPart);

        return self::CouponAttempts.'|'.self::actorKey(request(), $user).'|email:'.$emailPart.'|ip:'.request()->ip();
    }

    public static function reviewKey(User $user): string
    {
        return self::ReviewSubmissions.'|user:'.$user->id.'|ip:'.request()->ip();
    }

    protected static function actorKey(Request $request, ?User $user = null): string
    {
        $actor = $user ?? $request->user();

        if ($actor instanceof User) {
            return 'user:'.$actor->id;
        }

        if ($request->hasSession()) {
            return 'guest:'.$request->session()->getId();
        }

        return 'guest:no-session';
    }
}
