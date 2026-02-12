<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_screen')) return;

        Schema::table('role_screen', function (Blueprint $table) {
            if (!Schema::hasColumn('role_screen', 'can_create')) {
                $table->boolean('can_create')->default(true)->after('screen_id');
            }
            if (!Schema::hasColumn('role_screen', 'can_edit')) {
                $table->boolean('can_edit')->default(true)->after('can_create');
            }
            if (!Schema::hasColumn('role_screen', 'can_delete')) {
                $table->boolean('can_delete')->default(true)->after('can_edit');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('role_screen')) return;

        Schema::table('role_screen', function (Blueprint $table) {
            if (Schema::hasColumn('role_screen', 'can_delete')) $table->dropColumn('can_delete');
            if (Schema::hasColumn('role_screen', 'can_edit')) $table->dropColumn('can_edit');
            if (Schema::hasColumn('role_screen', 'can_create')) $table->dropColumn('can_create');
        });
    }
};

