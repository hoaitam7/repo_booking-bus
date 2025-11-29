<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'route_id',
        'bus_id',
        'departure_time',
        'ticket_price',
        'available_seats',
        'status'
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'ticket_price' => 'decimal:2'
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
