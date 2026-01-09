<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // If column already nullable, do nothing
        // (SQLite doesn't expose nullability easily; we just rebuild safely)

        DB::statement("PRAGMA foreign_keys=OFF;");

        // Create a new table with the same columns, but device_type nullable
        Schema::create('devices_new', function (Blueprint $table) {
            $table->increments('id');
            $table->string('device_id')->nullable();
            $table->string('name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('location')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('status_id')->nullable();
            $table->string('model_number')->nullable();
            $table->string('ip')->nullable();
            $table->string('utc_time_zone')->nullable();
            $table->integer('branch_id')->nullable();
            $table->string('serial_number')->nullable();

            // The fix:
            $table->string('device_type')->nullable();

            $table->string('port')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Copy data
        DB::statement("
            INSERT INTO devices_new (
                id, device_id, name, short_name, location, company_id, status_id,
                model_number, ip, utc_time_zone, branch_id, serial_number, device_type,
                port, created_at, updated_at
            )
            SELECT
                id, device_id, name, short_name, location, company_id, status_id,
                model_number, ip, utc_time_zone, branch_id, serial_number, device_type,
                port, created_at, updated_at
            FROM devices
        ");

        Schema::drop('devices');
        Schema::rename('devices_new', 'devices');

        DB::statement("PRAGMA foreign_keys=ON;");
    }

    public function down(): void
    {
        // optional: no-op for SQLite
    }
};
