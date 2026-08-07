<?php

namespace App\Policies;

class ReturnItemPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'returns';
}
