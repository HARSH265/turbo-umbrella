<?php 
namespace App\Enums; 
enum LateFeeType: string 
{ 
    case PERCENTAGE = 'percentage'; 
    case FIXED = 'fixed'; 
    }