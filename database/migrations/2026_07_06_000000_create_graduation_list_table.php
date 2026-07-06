<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_list', function (Blueprint $table) {
            $table->id();
            $table->string('matric_number')->unique();
            $table->string('name')->nullable();
            $table->string('course')->nullable();
            $table->string('centre')->nullable();
            $table->string('session', 20)->default('2025/2026');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_list');
    }
};
