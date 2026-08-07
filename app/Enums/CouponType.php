<?php

namespace App\Enums;

enum CouponType: string
{
    case Cart = 'cart';
    case Product = 'product';
    case Shipping = 'shipping';
}
