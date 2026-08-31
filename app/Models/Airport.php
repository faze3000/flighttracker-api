<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['iata_code', 'name', 'city', 'country', 'timezone', 'lat', 'lng'])]
class Airport extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'iata_code';

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:6',
            'lng' => 'decimal:6',
        ];
    }
}
