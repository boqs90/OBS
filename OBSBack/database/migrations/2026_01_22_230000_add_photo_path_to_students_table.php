<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) return;
        if (Schema::hasColumn('students', 'photo_path')) return;

        Schema::table('students', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('enrollmentStatus');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) return;
        if (!Schema::hasColumn('students', 'photo_path')) return;

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};

