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
            // Add new columns
            $table->boolean('course_paid')->default(false)->after('has_paid');
            $table->date('course_fee_reference')->nullable()->after('course_paid');
            $table->string('course_fee_date')->nullable()->after('course_fee_reference'); // Fixed typo
            $table->string('gender')->nullable()->after('course_fee_date');
            $table->string('place_of_birth')->nullable()->after('gender');
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
            // Drop the added columns
            $table->dropColumn('course_paid');
            $table->dropColumn('course_fee_reference');
            $table->dropColumn('course_fee_date');
            $table->dropColumn('gender');
            $table->dropColumn('place_of_birth');
        });
    }
};