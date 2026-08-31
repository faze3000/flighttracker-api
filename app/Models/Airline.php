<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['iata_code', 'name', 'alliance'])]
class Airline extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'iata_code';

    protected $keyType = 'string';

    public $timestamps = false;
}
