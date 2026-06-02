<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Emergency;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'booking' => Booking::class,
            'emergency' => Emergency::class,
        ]);
    }
}
