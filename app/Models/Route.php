<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'from_city',
        'to_city',
        'distance',
        'duration',
        'price',
        'img_url'
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function pickupPoints()
    {
        return $this->hasMany(PickupPoint::class);
    }
}
