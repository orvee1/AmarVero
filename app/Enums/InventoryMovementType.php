<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Adjustment = 'adjustment';
    case Restock = 'restock';
    case Sale = 'sale';
    case Return = 'return';
    case Reservation = 'reservation';
    case Release = 'release';
    case Cancellation = 'cancellation';
}
