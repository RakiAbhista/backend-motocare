<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\Emergency;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_type',
        'reference_id',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi Polymorphic ke Booking atau Emergency
    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'service_type', 'reference_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'reference_id');
    }
    
    public function emergency(): BelongsTo
    {
        return $this->belongsTo(Emergency::class, 'reference_id');
    }
}