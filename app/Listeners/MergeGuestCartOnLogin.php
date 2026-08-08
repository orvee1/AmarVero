<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Cart\CartManager;
use Illuminate\Auth\Events\Login;

class MergeGuestCartOnLogin
{
    public function __construct(protected CartManager $cartManager)
    {
        //
    }

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->cartManager->mergeGuestCartIntoUser($event->user);
        }
    }
}
