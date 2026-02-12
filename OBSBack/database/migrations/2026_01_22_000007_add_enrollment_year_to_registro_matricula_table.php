<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registro_matricula')) return;

        if (!Schema::hasColumn('registro_matricula', 'enrollmentYear')) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                $table->integer('enrollmentYear')->nullable()->after('enrollmentStatus');
            });
        }

        // Backfill: si hay estudiantes sin registro, crear uno
        if (Schema::hasTable('students')) {
            $rows = DB::table('students')
                ->leftJoin('registro_matricula', 'students.id', '=', 'registro_matricula.student_id')
                ->whereNull('registro_matricula.student_id')
                ->select('students.id', 'students.gradeCourse', 'students.enrollmentStatus', 'students.enrollmentYear')
                ->get();

            foreach ($rows as $s) {
                DB::table('registro_matricula')->insert([
                    'student_id' => $s->id,
                    'gradeCourse' => $s->gradeCourse ?? '',
                    'enrollmentStatus' => $s->enrollmentStatus ?? 'Activo',
                    'enrollmentYear' => $s->enrollmentYear ?? (int) date('Y'),
                    'enrolled_at' => date('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('registro_matricula')) return;
        if (!Schema::hasColumn('registro_matricula', 'enrollmentYear')) return;

        Schema::table('registro_matricula', function (Blueprint $table) {
            $table->dropColumn('enrollmentYear');
        });
    }
};

