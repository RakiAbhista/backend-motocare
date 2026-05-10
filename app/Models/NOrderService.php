<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NOrderService extends Model
{
    use HasFactory;

    protected $table = 'n_order_services';

    protected $fillable = [
        'order_id',
        'service_id',
        'additional_service',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}