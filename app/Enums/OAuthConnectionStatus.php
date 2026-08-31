<?php

namespace App\Enums;

enum OAuthConnectionStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Error = 'error';
}
