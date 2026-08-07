<?php

namespace App\Policies;

class CampaignPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'campaigns';
}
