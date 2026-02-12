<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_screen_overrides')) return;

        Schema::create('user_screen_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('screen_key', 200);
            $table->boolean('allowed')->default(false); // true=allow extra, false=deny (tiene prioridad)
            $table->timestamps();

            $table->unique(['user_id', 'screen_key'], 'user_screen_overrides_user_screen_unique');
            $table->index(['user_id', 'allowed'], 'user_screen_overrides_user_allowed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_screen_overrides');
    }
};

