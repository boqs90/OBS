<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees')) return;

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('fullName', 200);
            $table->string('email', 200)->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('identityNumber', 50)->nullable();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->date('entryDate')->nullable();
            $table->date('exitDate')->nullable();
            $table->string('status', 20)->default('Activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

