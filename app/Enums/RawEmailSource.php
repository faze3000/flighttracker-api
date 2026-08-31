<?php

namespace App\Enums;

enum RawEmailSource: string
{
    case Gmail = 'gmail';
    case Outlook = 'outlook';
    case Forwarded = 'forwarded';
}
