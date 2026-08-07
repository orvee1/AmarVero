<?php

namespace App\Policies;

class InventoryMovementPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'inventory-movements';
}
