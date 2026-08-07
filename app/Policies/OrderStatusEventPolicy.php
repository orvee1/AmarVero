<?php

namespace App\Policies;

class OrderStatusEventPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'orders';
}
