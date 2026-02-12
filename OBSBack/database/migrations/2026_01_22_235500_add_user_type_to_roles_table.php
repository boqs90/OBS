<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;
        if (Schema::hasColumn('roles', 'user_type')) return;

        Schema::table('roles', function (Blueprint $table) {
            $table->string('user_type', 30)->default('Usuario normal')->after('is_system');
        });

        // Migrar datos existentes:
        // - roles del sistema -> "Sistema"
        DB::table('roles')->where('is_system', 1)->update(['user_type' => 'Sistema']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) return;
        if (!Schema::hasColumn('roles', 'user_type')) return;

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
};

