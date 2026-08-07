<?php

namespace App\Policies;

class OrderItemPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'orders';
}
