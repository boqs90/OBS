<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;
        if (!Schema::hasTable('roles')) return;
        if (!Schema::hasColumn('users', 'role')) return;

        // Normalizar datos (evita problemas con FK por espacios)
        DB::statement("UPDATE users SET role = TRIM(role) WHERE role IS NOT NULL");
        DB::statement("UPDATE users SET role = 'Usuario' WHERE role IS NULL OR role = ''");

        // Asegurar que existan los roles usados por usuarios
        DB::table('roles')->insertOrIgnore([
            'name' => 'Usuario',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distinctRoles = DB::table('users')
            ->select('role')
            ->distinct()
            ->pluck('role')
            ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->values();

        foreach ($distinctRoles as $roleName) {
            DB::table('roles')->insertOrIgnore([
                'name' => $roleName,
                'is_system' => ($roleName === 'Administrador'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Relación DB: users.role -> roles.name (bloquea borrado si está en uso)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role')
                ->references('name')
                ->on('roles')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) return;
        if (!Schema::hasColumn('users', 'role')) return;

        Schema::table('users', function (Blueprint $table) {
            // nombre por defecto: users_role_foreign
            $table->dropForeign(['role']);
        });
    }
};
