<?php

namespace App\Policies;

class ProductCollectionPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'collections';
}
