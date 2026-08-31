<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['airport_iata', 'name', 'terminal', 'access_conditions'])]
class Lounge extends Model
{
    //
}
