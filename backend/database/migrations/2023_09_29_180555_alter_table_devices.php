<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('devices', function (Blueprint $table) {
            // Skip dropForeign on SQLite
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table('devices', function ($table) {
                    $table->dropForeign('devices_status_id_foreign'); // or the correct name
                    // other dropForeign/dropIndex that are not supported
                });
            }
            // $table->dropForeign('devices_device_type_check');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            //
        });
    }
};
