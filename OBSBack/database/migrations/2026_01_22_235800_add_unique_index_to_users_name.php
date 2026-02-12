<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;
        if (!Schema::hasColumn('users', 'name')) return;

        // Si ya existen duplicados, NO creamos índice único para no romper migraciones.
        // Igual queda bloqueado por validación al crear/editar usuarios.
        $hasDuplicates = DB::table('users')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            return;
        }

        // Intentar agregar índice único (si ya existe, no fallar).
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('name', 'users_name_unique');
            });
        } catch (\Throwable $e) {
            // Ignorar si el índice ya existe o el driver no lo soporta en este estado.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_name_unique');
            });
        } catch (\Throwable $e) {
            // Ignorar si no existe
        }
    }
};

