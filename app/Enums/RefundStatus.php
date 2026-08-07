<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
