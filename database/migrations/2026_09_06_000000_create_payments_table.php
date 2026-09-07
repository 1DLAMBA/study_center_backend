<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('pay_type');
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('personal_detail_id')->nullable();
            $table->unsignedBigInteger('clearance_request_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('personal_detail_id')
                ->references('id')
                ->on('personal_details')
                ->nullOnDelete();

            $table->foreign('clearance_request_id')
                ->references('id')
                ->on('clearance_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
