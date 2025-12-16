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
        Schema::create('device_temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->integer("company_id");
            $table->integer("branch_id")->nullable();
            $table->string("serial_number");
            $table->dateTime("log_time");
            $table->decimal("temparature", 8, 2);
            $table->decimal("humidity", 8, 2);
            $table->integer("temperature_serial_address")->nullable();
            $table->integer("device_id")->nullable();



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_temperature_logs');
    }
};
