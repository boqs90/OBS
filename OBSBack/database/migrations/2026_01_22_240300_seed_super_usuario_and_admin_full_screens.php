<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;
        if (!Schema::hasTable('screens')) return;
        if (!Schema::hasTable('role_screen')) return;

        $now = now();

        // 1) Rol fijo: Super usuario (NO se le pueden quitar permisos)
        if (Schema::hasColumn('roles', 'user_type')) {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Super usuario'],
                [
                    'is_system' => 1,
                    'user_type' => 'Super usuario',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        } else {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Super usuario'],
                [
                    'is_system' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 2) Rol Administrador: nace con todas las pantallas, pero SÍ se pueden modificar sus permisos
        if (Schema::hasColumn('roles', 'user_type')) {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Administrador'],
                [
                    'is_system' => 0,
                    'user_type' => 'Usuario normal',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        } else {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Administrador'],
                [
                    'is_system' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $superId = DB::table('roles')->where('name', 'Super usuario')->value('id');
        $adminId = DB::table('roles')->where('name', 'Administrador')->value('id');
        if (!$superId || !$adminId) return;

        $screenIds = DB::table('screens')->pluck('id');

        foreach ([$superId, $adminId] as $rid) {
            foreach ($screenIds as $sid) {
                DB::table('role_screen')->insertOrIgnore([
                    'role_id' => $rid,
                    'screen_id' => $sid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // No revertimos seeds.
    }
};

