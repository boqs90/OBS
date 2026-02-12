<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function normalize(?string $name): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        if ($name === '') $name = 'Usuario';
        return $name;
    }

    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;

        // Si hay FK users.role -> roles.name, esto ayuda a que actualizaciones de name no rompan usuarios.
        $hasUsers = Schema::hasTable('users') && Schema::hasColumn('users', 'role');
        $hasRoleScreen = Schema::hasTable('role_screen');

        $roles = DB::table('roles')->orderBy('id')->get(['id', 'name']);

        $canonByNormalized = [];

        foreach ($roles as $r) {
            $id = (int) $r->id;
            $current = (string) $r->name;
            $normalized = $this->normalize($current);

            // Si ya existe un rol “canon” con el mismo nombre normalizado, consolidamos.
            if (isset($canonByNormalized[$normalized])) {
                $canonId = (int) $canonByNormalized[$normalized];

                // Mover pivots role_screen al canon
                if ($hasRoleScreen) {
                    $screenIds = DB::table('role_screen')
                        ->where('role_id', $id)
                        ->pluck('screen_id');

                    $now = now();
                    foreach ($screenIds as $sid) {
                        DB::table('role_screen')->insertOrIgnore([
                            'role_id' => $canonId,
                            'screen_id' => (int) $sid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('role_screen')->where('role_id', $id)->delete();
                }

                // Mover usuarios que tengan el nombre exacto del rol duplicado
                if ($hasUsers) {
                    $canonName = DB::table('roles')->where('id', $canonId)->value('name');
                    $canonName = $this->normalize((string) $canonName);
                    DB::table('users')->where('role', $current)->update(['role' => $canonName]);
                }

                // Eliminar rol duplicado (ya no debe tener pivots)
                DB::table('roles')->where('id', $id)->delete();
                continue;
            }

            // Primer rol con ese nombre normalizado: lo marcamos como canon
            $canonByNormalized[$normalized] = $id;

            // Normalizar el nombre si tiene espacios raros (TRIM / colapsar)
            if ($normalized !== $current) {
                DB::table('roles')->where('id', $id)->update(['name' => $normalized]);

                // Si por alguna razón el FK no está o no cascadeara, aseguramos actualización en users.
                if ($hasUsers) {
                    DB::table('users')->where('role', $current)->update(['role' => $normalized]);
                }
            }
        }
    }

    public function down(): void
    {
        // No revertimos normalizaciones/deduplicación.
    }
};

