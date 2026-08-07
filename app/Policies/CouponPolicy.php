<?php

namespace App\Policies;

class CouponPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'coupons';
}
