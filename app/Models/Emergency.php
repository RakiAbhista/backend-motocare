<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emergency extends Model
{
    protected $fillable = [
        'user_id',
        'mechanic_id',
        'vehicle_id',
        'vehicle_model',
        'vehicle_brand',
        'vehicle_type',
        'plate_number',
        'workshop_id',
        'latitude',
        'longitude',
        'damage_photo',
        'complaint',
        'status',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function orderDetail()
    {
        return $this->morphOne(OrderDetail::class, 'source', 'service_type', 'reference_id');
    }

    public function order()
    {
        return $this->hasOneThrough(
            Order::class,
            OrderDetail::class,
            'reference_id',
            'id',
            'id',
            'order_id'
        )->where('order_details.service_type', 'emergency');
    }
}
