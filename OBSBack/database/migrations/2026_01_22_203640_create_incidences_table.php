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
        Schema::create('incidences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('General'); // General | Maestro
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('reported_by_user_id')->nullable();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->string('severity', 20)->default('Media'); // Baja | Media | Alta
            $table->string('status', 20)->default('Abierta'); // Abierta | En Proceso | Resuelta
            $table->date('occurred_at')->nullable();

            $table->index(['type', 'status']);
            $table->index(['occurred_at']);

            $table->timestamps();

            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->restrictOnDelete();

            $table->foreign('reported_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidences');
    }
};
