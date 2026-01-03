<?php
// app/Models/Booking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'user_id',
        'trip_id',
        'pickup_point_id',
        'seat_numbers',
        'passenger_name',
        'passenger_phone',
        'total_amount',
        'status',
        'payment_status',
        'payment_method'
    ];

    protected $casts = [
        'total_amount' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = (int)(now()->format('ymdHi') . rand(100, 999));
        });
    }
}
