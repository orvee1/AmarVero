<?php

namespace App\Policies;

class ShipmentPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'shipments';
}
