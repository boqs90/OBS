<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;

        // Asegurar que exista el rol "Sistema" como rol del sistema (2do en importancia).
        // Nota: is_system=true implica que no se puede editar/borrar desde la UI.
        if (Schema::hasColumn('roles', 'user_type')) {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Sistema'],
                [
                    'is_system' => 1,
                    'user_type' => 'Sistema',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Administrador debe ser el "Super usuario" (permiso total).
            DB::table('roles')->where('name', 'Administrador')->update([
                'is_system' => 1,
                'user_type' => 'Super usuario',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Sistema'],
                [
                    'is_system' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No revertimos seeds (seguro en entornos reales).
    }
};

