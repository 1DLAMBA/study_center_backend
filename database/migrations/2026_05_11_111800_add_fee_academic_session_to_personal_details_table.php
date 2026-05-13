<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personal_details', function (Blueprint $table) {
            $table->string('fee_academic_session', 20)
                ->nullable()
                ->after('course_fee_reference');
        });
    }

    public function down(): void
    {
        Schema::table('personal_details', function (Blueprint $table) {
            $table->dropColumn('fee_academic_session');
        });
    }
};
