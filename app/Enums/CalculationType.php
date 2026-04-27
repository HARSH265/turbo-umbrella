<?php

namespace App\Enums;

enum CalculationType: string
{
    case FIXED = 'fixed';
    case FLAT_TYPE = 'flat_type';
    case AREA_BASED = 'area_based';
}
