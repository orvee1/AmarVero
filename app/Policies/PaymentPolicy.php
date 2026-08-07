<?php

namespace App\Policies;

class PaymentPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'payments';
}
