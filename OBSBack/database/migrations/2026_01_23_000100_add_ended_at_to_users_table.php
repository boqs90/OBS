<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;
        if (Schema::hasColumn('users', 'ended_at')) return;

        Schema::table('users', function (Blueprint $table) {
            // Fecha de finalización al eliminar (soft delete)
            $table->dateTime('ended_at')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;
        if (!Schema::hasColumn('users', 'ended_at')) return;

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });
    }
};

