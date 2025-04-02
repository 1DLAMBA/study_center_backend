<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBioRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bio_registration', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('application_number'); // Foreign key column
            $table->foreign('application_number')
                ->references('id')
                ->on('personal_details')
                ->onDelete('cascade'); // Add cascading behavior if needed

            $table->string('next_of_kin')->nullable();
            $table->string('sponsor_address')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone_number')->nullable();
            $table->string('next_of_kin_address')->nullable();
            $table->string('nationality')->nullable();
            $table->string('level')->nullable();
            $table->string('mode_of_entry')->nullable();
            $table->string('session')->nullable();
            $table->string('subject_combination')->nullable();
            $table->timestamps(); // Created_at and Updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bio_registration');
    }
}
