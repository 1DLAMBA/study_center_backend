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
        Schema::create('personal_details', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->string('surname');
            $table->string('other_names');
            $table->date('date_of_birth');
            $table->string('marital_status');
            $table->string('phone_number');
            $table->string('address');
            $table->string('state_of_origin');
            $table->string('local_government');
            $table->string('ethnic_group')->nullable();
            $table->string('religion');
            $table->string('name_of_father');
            $table->string('father_state_of_origin');
            $table->string('father_place_of_birth');
            $table->string('mother_state_of_origin');
            $table->string('mother_place_of_birth');
            $table->string('applicant_occupation');
            $table->string('desired_study_cent');
            $table->text('working_experience')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_details');
    }
};
