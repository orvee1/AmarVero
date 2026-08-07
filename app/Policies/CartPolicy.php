<?php

namespace App\Policies;

class CartPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'carts';
}
