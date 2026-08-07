<?php

namespace App\Policies;

class StaticPagePolicy extends AdminPolicy
{
    protected string $permissionGroup = 'pages';
}
