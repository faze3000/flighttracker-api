<?php

namespace App\Enums;

enum TripStatus: string
{
    case Upcoming = 'upcoming';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
