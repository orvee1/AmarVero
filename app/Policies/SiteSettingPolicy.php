<?php

namespace App\Policies;

class SiteSettingPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'settings';
}
