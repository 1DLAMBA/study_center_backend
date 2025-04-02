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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('level_of_course')->nullable();
            $table->string('mode_of_course')->nullable();
            $table->decimal('fees_paid', 10, 2)->nullable();
            $table->string('subject_of_study')->nullable();
           $table->string('session')->nullable();
            $table->string('fees_trx_id')->nullable();
            $table->string('fees_reference')->nullable();
            $table->date('course_reg_date')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            //
        });
    }
};
