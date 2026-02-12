<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teachers')) return;

        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'legacy_no')) {
                $table->unsignedInteger('legacy_no')->nullable()->unique();
            }
            if (!Schema::hasColumn('teachers', 'observations')) {
                $table->string('observations', 200)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teachers')) return;

        Schema::table('teachers', function (Blueprint $table) {
            // Drop unique index if exists (MySQL default naming)
            try {
                $table->dropUnique('teachers_legacy_no_unique');
            } catch (Throwable $e) {
                // ignore
            }

            $cols = [];
            foreach (['legacy_no', 'observations'] as $col) {
                if (Schema::hasColumn('teachers', $col)) $cols[] = $col;
            }
            if (count($cols)) $table->dropColumn($cols);
        });
    }
};

