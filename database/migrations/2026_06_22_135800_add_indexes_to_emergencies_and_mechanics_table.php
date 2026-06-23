<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $table->index('mechanic_id');
        });

        Schema::table('mechanics', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $table->dropIndex(['mechanic_id']);
        });

        Schema::table('mechanics', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};