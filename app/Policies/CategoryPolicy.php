<?php

namespace App\Policies;

class CategoryPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'categories';
}
