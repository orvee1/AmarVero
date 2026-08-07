<?php

namespace App\Policies;

class UserPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'customers';
}
