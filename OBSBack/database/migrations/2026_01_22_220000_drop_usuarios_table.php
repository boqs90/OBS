<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Borra la tabla vieja `usuarios` (NO es `users`)
        if (!Schema::hasTable('usuarios')) return;
        Schema::drop('usuarios');
    }

    public function down(): void
    {
        // Re-crea `usuarios` solo si fuese necesario revertir.
        if (Schema::hasTable('usuarios')) return;

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
};

