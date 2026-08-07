<?php

namespace App\Policies;

class NewsletterSubscriberPolicy extends AdminPolicy
{
    protected string $permissionGroup = 'newsletter';
}
