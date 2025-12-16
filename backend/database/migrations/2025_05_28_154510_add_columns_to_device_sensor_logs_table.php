<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('device_sensor_logs', function (Blueprint $table) {
            $table->decimal("temperature_min", 8, 2)->nullable();
            $table->decimal("temperature_max", 8, 2)->nullable();
            $table->decimal("humidity_min", 8, 2)->nullable();
            $table->decimal("humidity_max", 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_sensor_logs', function (Blueprint $table) {
            $table->dropColumn('temperature_min');
            $table->dropColumn('temperature_max');
            $table->dropColumn('humidity_min');
            $table->dropColumn('humidity_max');
        });
    }
};
