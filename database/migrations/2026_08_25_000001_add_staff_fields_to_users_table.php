<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('centre_coordinator')->after('password');
            $table->string('study_centre')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('study_centre');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'study_centre', 'is_active']);
        });
    }
};
