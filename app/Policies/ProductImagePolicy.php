<?php

namespace App\Policies;

class ProductImagePolicy extends AdminPolicy
{
    protected string $permissionGroup = 'product-images';
}
