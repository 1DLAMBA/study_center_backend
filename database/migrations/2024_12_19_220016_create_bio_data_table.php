<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBioDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bio_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->nullable();
            $table->string('faculty')->nullable();
            $table->string('department')->nullable();
            $table->string('programme')->nullable();
            $table->string('level')->nullable();
            $table->string('current_semester')->nullable();
            $table->string('current_session')->nullable();
            $table->string('matric_number')->nullable();
            $table->string('mode_of_entry')->nullable();
            $table->string('study_mode')->nullable();
            $table->string('entry_year')->nullable();
            $table->string('program_duration')->nullable();
            $table->string('award_in_view')->nullable();
            $table->string('present_contact_address')->nullable();
            $table->string('permanent_home_address')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone_number')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('sponsor_address')->nullable();
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
        Schema::dropIfExists('bio_data');
    }
}
