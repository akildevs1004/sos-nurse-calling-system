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
        Schema::create('nurse_calling_logs', function (Blueprint $table) {
            $table->id();
            $table->integer("company_id")->nullable();
            $table->string("serial_number")->nullable();
            $table->string("switch1")->nullable();
            $table->string("switch2")->nullable();
            $table->string("date_time")->nullable();

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
        Schema::dropIfExists('nurse_calling_logs');
    }
};
