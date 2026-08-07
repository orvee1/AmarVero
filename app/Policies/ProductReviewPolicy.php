<?php

namespace App\Policies;

class ProductReviewPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'reviews';
}
