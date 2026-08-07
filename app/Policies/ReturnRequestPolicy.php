<?php

namespace App\Policies;

class ReturnRequestPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'returns';
}
