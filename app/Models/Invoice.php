<?php
// app/Models/Invoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'booking_id',
        'invoice_number',
        'total_amount',
        'status'
        // Chỉ các trường thực sự tồn tại trong bảng
    ];

    protected $casts = [
        'total_amount' => 'integer'
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
