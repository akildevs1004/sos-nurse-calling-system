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
        Schema::table('device_temperature_logs', function (Blueprint $table) {
            $table->integer("humidity_alarm")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_temperature_logs', function (Blueprint $table) {
            $table->dropColumn("humidity_alarm");
        });
    }
};
