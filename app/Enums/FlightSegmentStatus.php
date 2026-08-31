<?php

namespace App\Enums;

enum FlightSegmentStatus: string
{
    case Confirmed = 'confirmed';
    case Changed = 'changed';
    case Cancelled = 'cancelled';
}
