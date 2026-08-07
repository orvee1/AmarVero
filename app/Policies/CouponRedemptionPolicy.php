<?php

namespace App\Policies;

class CouponRedemptionPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'coupon-redemptions';
}
