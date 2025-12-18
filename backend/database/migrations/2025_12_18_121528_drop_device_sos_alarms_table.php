<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('device_sos_alarms');
    }

    public function down(): void
    {
        Schema::create('device_sos_alarms', function (Blueprint $table) {
            $table->id();
            // recreate columns ONLY if rollback is required
            $table->timestamps();
        });
    }
};
