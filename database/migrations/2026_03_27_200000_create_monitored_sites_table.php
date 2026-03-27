<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->integer('last_score')->nullable();
            $table->string('last_grade', 3)->nullable();
            $table->foreignId('last_scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('notify_score_drop')->default(true);
            $table->boolean('notify_cert_expiry')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_sites');
    }
};
