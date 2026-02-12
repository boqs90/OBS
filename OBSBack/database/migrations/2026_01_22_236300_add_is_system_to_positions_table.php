<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('positions')) return;

        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('status');
            }
        });

        // Marcar como "del sistema" los cargos que vienen del código (seed) + Directora
        $systemNames = [
            'Directora',
            'Docente Prekinder',
            'Docente de Kinder',
            'Docente 1er Grado',
            'Docente 2do Grado',
            'Docente de 3er Grado',
            'Docente 4to Grado',
            'Docente 5to Grado',
            'Docente 6to Grado',
            'Docente de 7mo Grado',
            'Docente de 8vo Grado',
            'Docente 9no Grado',
            'Docente de 10mo Grado',
            'Docente de 11mo Grado',
            'Docente de Arte',
            'Docente de Educ. Física',
            'Docente de Informática',
            'Docente de Español y CCSS',
            'Docente de Música',
            'Maestra asistente de Kinder',
            'Maestra asistente de Prekinder',
            'Maestra asistente',
        ];

        $systemNamesLower = array_map(fn ($n) => mb_strtolower((string) $n, 'UTF-8'), $systemNames);

        DB::table('positions')
            ->whereIn(DB::raw('LOWER(name)'), $systemNamesLower)
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('positions')) return;

        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};

