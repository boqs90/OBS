<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registro_matricula') || !Schema::hasTable('students')) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $hasFk = DB::select(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'registro_matricula' AND CONSTRAINT_NAME = 'registro_matricula_student_id_foreign' LIMIT 1",
            [$dbName]
        );

        if (empty($hasFk)) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                $table->foreign('student_id', 'registro_matricula_student_id_foreign')
                    ->references('id')
                    ->on('students')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('registro_matricula')) {
            return;
        }

        Schema::table('registro_matricula', function (Blueprint $table) {
            try {
                $table->dropForeign('registro_matricula_student_id_foreign');
            } catch (\Throwable $e) {
            }
        });
    }
};
