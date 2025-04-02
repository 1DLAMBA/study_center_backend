<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_details', function (Blueprint $table) {
            //
            $table->string('nin')->nullable();
            $table->string( 'scratchcard_pin_1')->nullable();
            $table->string('scratchcard_serial')->nullable();
            $table->string('scratchcard_upload')->nullable();
            $table->string('passport')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_details', function (Blueprint $table) {
            //
        });
    }
};
