<?php

namespace App\Policies;

class ProductPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'products';
}
