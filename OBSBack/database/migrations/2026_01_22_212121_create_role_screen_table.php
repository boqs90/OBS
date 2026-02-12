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
        // Si por orden de ejecución aún no existe screens, lo creamos y seed-eamos aquí.
        if (!Schema::hasTable('screens')) {
            Schema::create('screens', function (Blueprint $table) {
                $table->id();
                $table->string('key', 200)->unique();
                $table->string('label', 200);
                $table->string('group', 200)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });

            $now = now();
            $screens = [
                ['key' => '/dashboard', 'label' => 'Resumen', 'group' => null, 'sort_order' => 10],
                ['key' => '/matricula', 'label' => 'Matrícula', 'group' => null, 'sort_order' => 20],
                ['key' => '/billing', 'label' => 'Pagos', 'group' => null, 'sort_order' => 30],
                ['key' => '/rtl', 'label' => 'Reportes', 'group' => null, 'sort_order' => 40],
                ['key' => '/profile', 'label' => 'Perfiles', 'group' => null, 'sort_order' => 50],

                ['key' => '/registro/alumnos', 'label' => 'Registro Alumnos', 'group' => 'Registro', 'sort_order' => 110],
                ['key' => '/registro/maestros', 'label' => 'Registro Maestros', 'group' => 'Registro', 'sort_order' => 120],
                ['key' => '/registro/empleados', 'label' => 'Registro Empleados', 'group' => 'Registro', 'sort_order' => 130],
                ['key' => '/registro/cargos', 'label' => 'Cargos', 'group' => 'Registro', 'sort_order' => 140],
                ['key' => '/registro/grados', 'label' => 'Grados', 'group' => 'Registro', 'sort_order' => 150],

                ['key' => '/notificaciones', 'label' => 'Notificaciones', 'group' => 'Administración de usuarios', 'sort_order' => 210],
                ['key' => '/sesiones', 'label' => 'Sesiones', 'group' => 'Administración de usuarios', 'sort_order' => 215],
                ['key' => '/tables', 'label' => 'Administrar usuarios', 'group' => 'Administración de usuarios', 'sort_order' => 220],
                ['key' => '/roles', 'label' => 'Administrar roles', 'group' => 'Administración de usuarios', 'sort_order' => 230],
            ];

            foreach ($screens as $s) {
                DB::table('screens')->insertOrIgnore([
                    'key' => $s['key'],
                    'label' => $s['label'],
                    'group' => $s['group'],
                    'sort_order' => $s['sort_order'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (!Schema::hasTable('role_screen')) {
            Schema::create('role_screen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('screen_id');
                $table->timestamps();
            });
        }

        // Agregar constraints de forma segura (por si la tabla quedó creada a medias)
        $uniqueName = 'role_screen_role_id_screen_id_unique';
        $hasUnique = DB::select("SHOW INDEX FROM role_screen WHERE Key_name = ?", [$uniqueName]);
        if (empty($hasUnique)) {
            DB::statement("ALTER TABLE role_screen ADD UNIQUE {$uniqueName} (role_id, screen_id)");
        }

        // Foreign keys (nombres por defecto de Laravel)
        $dbName = DB::getDatabaseName();

        $hasFkRole = DB::select(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'role_screen' AND CONSTRAINT_NAME = 'role_screen_role_id_foreign' LIMIT 1",
            [$dbName]
        );
        if (empty($hasFkRole)) {
            DB::statement("ALTER TABLE role_screen ADD CONSTRAINT role_screen_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE");
        }

        $hasFkScreen = DB::select(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'role_screen' AND CONSTRAINT_NAME = 'role_screen_screen_id_foreign' LIMIT 1",
            [$dbName]
        );
        if (empty($hasFkScreen)) {
            DB::statement("ALTER TABLE role_screen ADD CONSTRAINT role_screen_screen_id_foreign FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE");
        }

        // Asignar todas las pantallas al rol del sistema "Administrador"
        $adminRoleId = DB::table('roles')->where('name', 'Administrador')->value('id');
        if ($adminRoleId) {
            $now = now();
            $screenIds = DB::table('screens')->pluck('id');
            foreach ($screenIds as $sid) {
                DB::table('role_screen')->insertOrIgnore([
                    'role_id' => $adminRoleId,
                    'screen_id' => $sid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_screen');
    }
};
