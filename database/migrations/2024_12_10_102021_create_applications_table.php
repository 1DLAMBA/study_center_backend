<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('reference')->nullable();
            $table->string('passport')->nullable();
            $table->boolean('has_paid')->nullable();
            $table->string('programme')->nullable();
            $table->string('application_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
}

