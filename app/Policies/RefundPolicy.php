<?php

namespace App\Policies;

class RefundPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'refunds';
}
