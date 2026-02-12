<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;

        // Administrador: fijo (no borrable / no editable)
        DB::table('roles')->where('name', 'Administrador')->update([
            'is_system' => 1,
            'user_type' => 'Super usuario',
            'updated_at' => now(),
        ]);

        // Sistema: debe poder borrarse (no es rol fijo)
        DB::table('roles')->where('name', 'Sistema')->update([
            'is_system' => 0,
            'user_type' => 'Sistema',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // no-op
    }
};

