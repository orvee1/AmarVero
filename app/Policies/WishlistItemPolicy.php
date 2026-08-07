<?php

namespace App\Policies;

class WishlistItemPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'wishlists';
}
