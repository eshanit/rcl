<?php

namespace App\Enums;

enum MedicationStatusType: string
{
    //
    case START_OR_RESTART = 'Start/Restart';
    case CONTINUED = 'Continued';
    case STOP = 'Stop';
    case NO = 'No';
    case NOT_APPLICABLE = 'NA';
}
