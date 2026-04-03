<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->string('tier', 10)->default('free')->after('uid');
            $table->foreignId('user_id')->nullable()->after('tier')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['tier', 'user_id']);
        });
    }
};
