<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration

{
    public function up()
    {
        Schema::table('personal_details', function (Blueprint $table) {
            $table->string('surname')->nullable()->change();
            $table->string('other_names')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
            $table->string('marital_status')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('state_of_origin')->nullable()->change();
            $table->string('local_government')->nullable()->change();
            $table->string('religion')->nullable()->change();
            $table->string('name_of_father')->nullable()->change();
            $table->string('father_state_of_origin')->nullable()->change();
            $table->string('father_place_of_birth')->nullable()->change();
            $table->string('mother_state_of_origin')->nullable()->change();
            $table->string('mother_place_of_birth')->nullable()->change();
            $table->string('applicant_occupation')->nullable()->change();
            $table->string('desired_study_cent')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('personal_details', function (Blueprint $table) {
            $table->string('surname')->nullable(false)->change();
            $table->string('other_names')->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
            $table->string('marital_status')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('state_of_origin')->nullable(false)->change();
            $table->string('local_government')->nullable(false)->change();
            $table->string('religion')->nullable(false)->change();
            $table->string('name_of_father')->nullable(false)->change();
            $table->string('father_state_of_origin')->nullable(false)->change();
            $table->string('father_place_of_birth')->nullable(false)->change();
            $table->string('mother_state_of_origin')->nullable(false)->change();
            $table->string('mother_place_of_birth')->nullable(false)->change();
            $table->string('applicant_occupation')->nullable(false)->change();
            $table->string('desired_study_cent')->nullable(false)->change();
        });
    }
};
