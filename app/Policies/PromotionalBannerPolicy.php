<?php

namespace App\Policies;

class PromotionalBannerPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'banners';
}
