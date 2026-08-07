<?php

namespace App\Policies;

class CustomerAddressPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'customers';
}
