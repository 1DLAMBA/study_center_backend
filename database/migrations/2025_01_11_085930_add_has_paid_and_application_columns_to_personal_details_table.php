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
        Schema::table('personal_details', function (Blueprint $table) {
            $table->boolean('has_paid')->default(false)->after('desired_study_cent'); // Replace 'last_column_name' with the actual column name after which you want to add this column
            $table->date('application_date')->nullable()->after('has_paid');
            $table->string('application_trxid')->nullable()->after('application_date');
            $table->string('application_reference')->nullable()->after('application_trxid');
            $table->string('has_admission')->nullable()->after('application_reference');
            $table->string('matric_number')->nullable()->after('has_admission');
            $table->string('olevel1')->nullable()->after('matric_number');
            $table->string('course')->nullable()->after('olevel1');
            $table->string('school')->nullable()->after('course');
            $table->string('olevel2')->nullable()->after('school');
            $table->string('email')->nullable()->after('olevel2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_details', function (Blueprint $table) {
            $table->dropColumn(['has_paid', 'application_date', 'application_trxid', 'application_reference']);
        });
    }
};
