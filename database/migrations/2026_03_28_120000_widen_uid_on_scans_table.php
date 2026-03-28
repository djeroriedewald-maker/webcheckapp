<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Drop the existing unique index before modifying the column length,
            // then re-add it — avoids "Duplicate key name" on servers where
            // the index already exists from a prior migration.
            $table->dropUnique(['uid']);
            $table->string('uid', 16)->nullable()->change();
            $table->unique('uid');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropUnique(['uid']);
            $table->string('uid', 8)->nullable()->change();
            $table->unique('uid');
        });
    }
};
