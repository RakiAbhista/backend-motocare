<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'workshop_id',
        'service_id',
        'complaint',
        'damage_photo',
        'booking_date',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
