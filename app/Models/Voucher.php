<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'discount_value',
        'discount_type',
        'min_spend',
        'expiry_date',
        'usage_limit',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
