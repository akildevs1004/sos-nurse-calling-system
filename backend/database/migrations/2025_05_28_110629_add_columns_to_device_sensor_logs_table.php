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
            $table->datetime("alarm_start_datetime")->nullable();
            $table->datetime("alarm_end_datetime")->nullable();
            $table->integer("alarm_status")->nullable();
            $table->string("alarm_type")->nullable();
            $table->integer("response_minutes")->nullable();
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
            $table->dropColumn("alarm_start_datetime");
            $table->dropColumn("alarm_end_datetime");
            $table->dropColumn("alarm_status");
            $table->dropColumn("alarm_type");
            $table->dropColumn("response_minutes");
        });
    }
};
