<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->string('uid', 8)->nullable()->unique()->after('id');
        });

        // Backfill any existing rows that have no uid yet
        DB::table('scans')->whereNull('uid')->orderBy('id')->each(function ($scan) {
            DB::table('scans')->where('id', $scan->id)->update(['uid' => Str::random(8)]);
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
