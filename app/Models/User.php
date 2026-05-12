<?php

namespace App\Models;

// 1. Tambahkan baris ini untuk Sanctum (Token API)
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. Tambahkan HasApiTokens di sini
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'name',
        'phone_number',
        'email',
        'password',
        'points',
        // 3. Tambahkan kolom OTP agar bisa diisi saat forgot password
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Mutator: Normalize role ke lowercase saat disimpan
     */
    protected function role(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn($value) => strtolower($value),
            get: fn($value) => strtolower($value),
        );
    }

    // Relationships
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function emergencies()
    {
        return $this->hasMany(Emergency::class);
    }

    public function mechanic()
    {
        return $this->hasOne(Mechanic::class);
    }
}