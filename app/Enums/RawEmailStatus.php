<?php

namespace App\Enums;

enum RawEmailStatus: string
{
    case Pending = 'pending';
    case Parsed = 'parsed';
    case Irrelevant = 'irrelevant';
    case Failed = 'failed';
}
