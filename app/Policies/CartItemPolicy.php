<?php

namespace App\Policies;

class CartItemPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'carts';
}
