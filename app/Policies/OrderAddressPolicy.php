<?php

namespace App\Policies;

class OrderAddressPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'orders';
}
