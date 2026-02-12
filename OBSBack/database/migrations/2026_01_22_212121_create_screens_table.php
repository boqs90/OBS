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
        $created = false;
        if (!Schema::hasTable('screens')) {
            Schema::create('screens', function (Blueprint $table) {
                $table->id();
                $table->string('key', 200)->unique();   // usamos el path de la ruta (ej: /dashboard)
                $table->string('label', 200);
                $table->string('group', 200)->nullable(); // ej: Registro, Administración de usuarios
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
            $created = true;
        }

        // Pantallas disponibles (opciones de menú) (se inserta siempre con insertOrIgnore)
        $now = now();
        $screens = [
            ['key' => '/dashboard', 'label' => 'Resumen', 'group' => null, 'sort_order' => 10],
            ['key' => '/matricula', 'label' => 'Matrícula', 'group' => null, 'sort_order' => 20],
            ['key' => '/billing', 'label' => 'Pagos', 'group' => null, 'sort_order' => 30],
            ['key' => '/rtl', 'label' => 'Reportes', 'group' => null, 'sort_order' => 40],
            ['key' => '/profile', 'label' => 'Perfiles', 'group' => null, 'sort_order' => 50],

            // Submenú Registro
            ['key' => '/registro/alumnos', 'label' => 'Registro Alumnos', 'group' => 'Registro', 'sort_order' => 110],
            ['key' => '/registro/maestros', 'label' => 'Registro Maestros', 'group' => 'Registro', 'sort_order' => 120],
            ['key' => '/registro/empleados', 'label' => 'Registro Empleados', 'group' => 'Registro', 'sort_order' => 130],
            ['key' => '/registro/cargos', 'label' => 'Cargos', 'group' => 'Registro', 'sort_order' => 140],
            ['key' => '/registro/grados', 'label' => 'Grados', 'group' => 'Registro', 'sort_order' => 150],

            // Submenú Usuarios
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
