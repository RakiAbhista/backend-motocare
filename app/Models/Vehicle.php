<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_type',
        'brand',
        'model',
        'plate_number',
        'manufacturing_year',
        'registration_doc',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function emergencies()
    {
        return $this->hasMany(Emergency::class);
    }
}
