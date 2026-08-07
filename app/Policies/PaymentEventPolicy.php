<?php

namespace App\Policies;

class PaymentEventPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'payments';
}
