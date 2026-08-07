<?php

namespace App\Policies;

class WishlistPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'wishlists';
}
