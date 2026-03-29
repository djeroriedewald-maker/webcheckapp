<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Used by DashboardController::history() and ScanController::show()
            $table->index(['host', 'status', 'completed_at'], 'scans_host_status_completed_idx');

            // Used by ScanController::show() for percentile calculation
            $table->index(['status', 'score'], 'scans_status_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex('scans_host_status_completed_idx');
            $table->dropIndex('scans_status_score_idx');
        });
    }
};
