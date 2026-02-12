<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registro_matricula')) return;

        Schema::create('registro_matricula', function (Blueprint $table) {
            $table->id();
            // La tabla `students` puede no existir aún por el orden de migraciones.
            $table->unsignedBigInteger('student_id');

            // Datos de matrícula (los que hoy ya usas en el frontend)
            $table->string('gradeCourse', 200);
            $table->string('enrollmentStatus', 50); // Activo / Inactivo / Pendiente
            $table->date('enrolled_at')->nullable();

            $table->timestamps();

            // Un registro de matrícula por estudiante (si luego quieres historial, quitamos este unique)
            $table->unique('student_id');
        });

        // Agregar FK solo si existe la tabla referenciada.
        if (Schema::hasTable('students')) {
            Schema::table('registro_matricula', function (Blueprint $table) {
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_matricula');
    }
};

