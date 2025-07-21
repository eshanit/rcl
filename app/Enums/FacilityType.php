<?php

namespace App\Enums;

enum FacilityType: string
{
    case HOSPITAL = 'Hospital';
    case PERIPHERAL = 'Peripheral';
    case NOT_APPLICABLE = 'Not Applicable';
}
