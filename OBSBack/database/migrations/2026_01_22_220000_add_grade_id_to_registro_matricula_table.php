<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registro_matricula') || !Schema::hasTable('grades')) {
            return;
        }

        // 1) Agregar columna grade_id (nullable para no romper datos existentes)
        if (!Schema::hasColumn('registro_matricula', 'grade_id')) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                $table->unsignedBigInteger('grade_id')->nullable()->after('student_id');
            });
        }

        // 2) Backfill (best-effort): si gradeCourse coincide con grades.name, linkear
        // Nota: si gradeCourse tiene secciones (ej. "7mo A") y no coincide, queda null.
        DB::statement("
            UPDATE registro_matricula rm
            JOIN grades g ON g.name = rm.gradeCourse
            SET rm.grade_id = g.id
            WHERE rm.grade_id IS NULL
        ");

        // 3) Crear FK con RESTRICT/NO ACTION (no permitir borrar un grado con registros)
        // Evita duplicar constraint si ya existe.
        $fkExists = false;
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'registro_matricula'
                  AND COLUMN_NAME = 'grade_id'
                  AND REFERENCED_TABLE_NAME = 'grades'
            ");
            $fkExists = !empty($constraints);
        } catch (\Throwable $e) {
            // Si falla esta verificación, igual intentamos crear la FK.
            $fkExists = false;
        }

        if (!$fkExists) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                $table->foreign('grade_id', 'registro_matricula_grade_id_fk')
                    ->references('id')
                    ->on('grades')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('registro_matricula')) {
            return;
        }

        if (Schema::hasColumn('registro_matricula', 'grade_id')) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                // El nombre del constraint lo fijamos arriba
                try {
                    $table->dropForeign('registro_matricula_grade_id_fk');
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('grade_id');
            });
        }
    }
};

