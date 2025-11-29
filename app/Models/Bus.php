<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'bus_name',
        'license_plate',
        'bus_type',
        'total_seats',
        'utilities'
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
