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
        Schema::table('device_sos_rooms', function (Blueprint $table) {
            // If duplicates already exist, this will fail until you clean them (see below).
            $table->unique(['device_id', 'room_id'], 'uniq_device_room');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_sos_rooms', function (Blueprint $table) {
            $table->dropUnique('uniq_device_room');
        });
    }
};
