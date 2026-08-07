<?php

namespace App\Policies;

class OrderPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'orders';
}
