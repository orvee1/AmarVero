<?php

namespace App\Policies;

class ProductVariantPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'product-variants';
}
