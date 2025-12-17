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
        Schema::create('device_sos_room_logs', function (Blueprint $table) {
            $table->id();
            $table->integer("company_id")->nullable();
            $table->integer("branch_id")->nullable();
            $table->integer("device_id");
            $table->integer("device_sos_room_table_id")->nullable();
            $table->integer("room_id")->nullable();
            $table->integer("serial_number")->nullable();
            $table->string("room_name")->nullable();
            $table->boolean("sos_status");
            $table->integer("on_code")->nullable();
            $table->integer("off_code")->nullable();
            $table->datetime("created_datetime")->nullable();
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
        Schema::dropIfExists('device_sos_room_logs');
    }
};
