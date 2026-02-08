<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_detail_id');
            $table->string('matric_number');
            $table->string('status')->default('pending');
            $table->string('fees_receipt_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('acceptance_paid')->default(false);
            $table->string('acceptance_reference')->nullable();
            $table->timestamp('acceptance_paid_at')->nullable();
            $table->timestamps();

            $table->foreign('personal_detail_id')
                ->references('id')
                ->on('personal_details')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_requests');
    }
};
