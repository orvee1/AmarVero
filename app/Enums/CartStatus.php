<?php

namespace App\Enums;

enum CartStatus: string
{
    case Active = 'active';
    case Merged = 'merged';
    case Converted = 'converted';
    case Abandoned = 'abandoned';
}
