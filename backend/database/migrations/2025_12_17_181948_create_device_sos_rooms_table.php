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
        Schema::create('device_sos_rooms', function (Blueprint $table) {
            $table->id();
            $table->integer("device_id");
            $table->string("serial_number");
            $table->string("name")->nullable();
            $table->integer("room_id");
            $table->integer("on_code")->nullable();
            $table->integer("off_code")->nullable();
            $table->boolean("status")->default(true);
            $table->boolean("alarm_status")->default(false);

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
        Schema::dropIfExists('device_sos_rooms');
    }
};
