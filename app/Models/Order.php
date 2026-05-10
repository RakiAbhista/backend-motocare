<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'mechanic_id',
        'voucher_id',
        'total_price',
        'status',
        'payment_status',
        'payment_type',
        'transaction_id',
        'payment_url',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // Relasi ke sumber order (Booking / Emergency)
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    // Relasi ke list service & additional service
    public function orderServices()
    {
        return $this->hasMany(NOrderService::class, 'order_id');
    }
}