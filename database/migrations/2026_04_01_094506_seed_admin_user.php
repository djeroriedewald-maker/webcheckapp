<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::updateOrCreate(
            ['email' => 'djeroriedewald@gmail.com'],
            [
                'name'     => 'Djero Riedewald',
                'password' => 'Djero95586!@!',
                'is_admin' => true,
            ],
        );
    }

    public function down(): void
    {
        User::where('email', 'djeroriedewald@gmail.com')->update(['is_admin' => false]);
    }
};
