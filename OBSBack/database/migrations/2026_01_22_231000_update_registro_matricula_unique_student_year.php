<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registro_matricula')) return;
        if (!Schema::hasColumn('registro_matricula', 'enrollmentYear')) return;

        // Asegurar índice normal por student_id (requerido por FK) antes de tocar el unique
        Schema::table('registro_matricula', function (Blueprint $table) {
            try {
                $table->index('student_id', 'registro_matricula_student_id_index');
            } catch (Throwable $e) {
                // ignore
            }
        });

        // Quitar unique anterior por student_id (si existe)
        Schema::table('registro_matricula', function (Blueprint $table) {
            try {
                $table->dropUnique('registro_matricula_student_id_unique');
            } catch (Throwable $e) {
                // ignore
            }
        });

        // Un único registro por (estudiante, año)
        Schema::table('registro_matricula', function (Blueprint $table) {
            try {
                $table->unique(['student_id', 'enrollmentYear'], 'registro_matricula_student_year_unique');
            } catch (Throwable $e) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('registro_matricula')) return;

        Schema::table('registro_matricula', function (Blueprint $table) {
            try {
                $table->dropUnique('registro_matricula_student_year_unique');
            } catch (Throwable $e) {
                // ignore
            }

            try {
                $table->unique('student_id');
            } catch (Throwable $e) {
                // ignore
            }
        });
    }
};

