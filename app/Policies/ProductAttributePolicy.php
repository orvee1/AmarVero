<?php

namespace App\Policies;

class ProductAttributePolicy extends AdminPolicy
{
    protected string $permissionGroup = 'attributes';
}
