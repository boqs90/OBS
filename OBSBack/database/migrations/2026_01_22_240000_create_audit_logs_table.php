<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Usuario que ejecutó la acción (actor)
            $table->unsignedBigInteger('actor_user_id')->nullable();

            // Acción: login/logout/user_created/user_updated/user_deleted/etc
            $table->string('action', 60);

            // Entidad afectada (opcional)
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('description', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['actor_user_id', 'created_at']);
            $table->index(['action', 'created_at']);

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

