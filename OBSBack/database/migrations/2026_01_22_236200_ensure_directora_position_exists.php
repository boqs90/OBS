<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('positions')) return;

        $now = now();
        $target = 'Directora';

        // Si existe con cualquier variante de mayúsculas/minúsculas, normalizar y activar.
        $existingId = DB::table('positions')
            ->whereRaw('LOWER(name) = ?', ['directora'])
            ->value('id');

        if ($existingId) {
            DB::table('positions')
                ->where('id', $existingId)
                ->update([
                    'name' => $target,
                    'status' => 'Activo',
                    'updated_at' => $now,
                ]);
            return;
        }

        // Si no existe, crearlo.
        DB::table('positions')->insert([
            'name' => $target,
            'description' => null,
            'status' => 'Activo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // No eliminarlo en rollback: es obligatorio.
    }
};

