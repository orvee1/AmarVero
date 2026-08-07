<?php

namespace App\Policies;

class ShippingMethodPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'shipping-settings';
}
