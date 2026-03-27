<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('host')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('grade', 4)->nullable();
            $table->json('results')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['url', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
