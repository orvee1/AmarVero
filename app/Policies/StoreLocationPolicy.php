<?php

namespace App\Policies;

class StoreLocationPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'store-locations';
}
