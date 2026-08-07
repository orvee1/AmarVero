<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case Manual = 'manual';
    case Card = 'card';
    case MobileBanking = 'mobile_banking';
    case BankTransfer = 'bank_transfer';
}
