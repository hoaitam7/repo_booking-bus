<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    protected $fillable = [
        'route_id',
        'name',
        'address'
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
