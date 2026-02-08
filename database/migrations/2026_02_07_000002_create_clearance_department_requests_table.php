<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_department_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clearance_request_id');
            $table->unsignedBigInteger('clearance_department_id');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('clearance_request_id')
                ->references('id')
                ->on('clearance_requests')
                ->onDelete('cascade');
            $table->foreign('clearance_department_id')
                ->references('id')
                ->on('clearance_departments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_department_requests');
    }
};
