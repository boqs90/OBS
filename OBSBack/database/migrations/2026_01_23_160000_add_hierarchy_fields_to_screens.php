<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            // Agregar campos para jerarquía de 3 niveles
            $table->string('section', 200)->nullable()->after('label');        // Nivel 1: Sección de menú
            $table->string('category', 200)->nullable()->after('section');     // Nivel 2: Categoría
            $table->string('link_type', 50)->default('link')->after('category'); // Nivel 3: Tipo de enlace
            $table->unsignedInteger('parent_id')->nullable()->after('link_type'); // Para estructura jerárquica
            
            // Índices para mejor rendimiento
            $table->index(['section', 'category', 'link_type']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->dropIndex(['section', 'category', 'link_type']);
            $table->dropIndex('parent_id');
            $table->dropColumn(['section', 'category', 'link_type', 'parent_id']);
        });
    }
};
