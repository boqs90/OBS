<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers')) return;

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('specialty')->nullable(); // Ej: Matemáticas, Inglés, etc.
            $table->string('status')->default('Activo'); // Activo / Inactivo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};

