<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teachers')) return;

        Schema::table('teachers', function (Blueprint $table) {
            // OJO: usamos nullable para no romper datos existentes.
            if (!Schema::hasColumn('teachers', 'birthDate')) {
                $table->date('birthDate')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'position')) {
                $table->string('position')->nullable(); // Cargo
            }
            if (!Schema::hasColumn('teachers', 'identityNumber')) {
                $table->string('identityNumber')->nullable()->unique(); // Número de identidad
            }
            if (!Schema::hasColumn('teachers', 'entryDate')) {
                $table->date('entryDate')->nullable(); // Fecha de ingreso
            }
            if (!Schema::hasColumn('teachers', 'exitDate')) {
                $table->date('exitDate')->nullable(); // Fecha de egreso (opcional)
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teachers')) return;

        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'identityNumber')) {
                $table->dropUnique(['identityNumber']);
            }
            $cols = [];
            foreach (['birthDate', 'position', 'identityNumber', 'entryDate', 'exitDate'] as $col) {
                if (Schema::hasColumn('teachers', $col)) $cols[] = $col;
            }
            if (count($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

