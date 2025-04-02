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
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_number')->unique(); // Foreign key for personal_details
            $table->string('first_school')->nullable();
            $table->string('first_course')->nullable();
            $table->string('p_school_name_1')->nullable();
            $table->date('p_school_from_1')->nullable();
            $table->date('p_school_to_1')->nullable();
            $table->string('p_school_name_2')->nullable();
            $table->date('p_school_from_2')->nullable();
            $table->date('p_school_to_2')->nullable();
            $table->string('s_school_name_1')->nullable();
            $table->date('s_school_from_1')->nullable();
            $table->date('s_school_to_1')->nullable();
            $table->string('s_school_name_2')->nullable();
            $table->date('s_school_from_2')->nullable();
            $table->date('s_school_to_2')->nullable();
            $table->string('second_school')->nullable();
            $table->string('second_course')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('application_number')->references('id')->on('personal_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_details');
    }
};
