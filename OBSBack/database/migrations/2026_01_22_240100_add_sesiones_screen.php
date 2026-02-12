<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('screens')) return;

        DB::table('screens')->insertOrIgnore([
            'key' => '/sesiones',
            'label' => 'Sesiones',
            'group' => 'Administración de usuarios',
            'sort_order' => 35,
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('screens')) return;
        DB::table('screens')->where('key', '/sesiones')->delete();
    }
};

