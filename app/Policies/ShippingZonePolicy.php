<?php

namespace App\Policies;

class ShippingZonePolicy extends AdminPolicy
{
    protected string $permissionGroup = 'shipping-settings';
}
