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
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Se crean como columnas simples porque la tabla `students` puede no existir aún por el orden de migraciones.
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('payment_concept_id');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue'])->default('pending');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable(); // cash, transfer, card, etc.
            $table->text('notes')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Agregar llaves foráneas solo si existen las tablas referenciadas.
        if (Schema::hasTable('students')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('payment_concepts')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('payment_concept_id')->references('id')->on('payment_concepts')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
