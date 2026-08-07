<?php

namespace App\Policies;

class BrandPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'brands';
}
