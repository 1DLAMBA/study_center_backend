<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_requests', function (Blueprint $table) {
            $table->boolean('fee_override')->default(false)->after('acceptance_paid_at');
            $table->text('fee_override_reason')->nullable()->after('fee_override');
            $table->unsignedBigInteger('fee_override_by')->nullable()->after('fee_override_reason');
            $table->timestamp('fee_override_at')->nullable()->after('fee_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('clearance_requests', function (Blueprint $table) {
            $table->dropColumn([
                'fee_override',
                'fee_override_reason',
                'fee_override_by',
                'fee_override_at',
            ]);
        });
    }
};
