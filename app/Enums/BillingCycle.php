<?php

namespace App\Enums;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case HALF_YEARLY = 'half_yearly';
    case YEARLY = 'yearly';
}