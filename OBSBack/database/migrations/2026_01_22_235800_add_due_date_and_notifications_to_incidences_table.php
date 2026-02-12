<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incidences')) return;

        Schema::table('incidences', function (Blueprint $table) {
            if (!Schema::hasColumn('incidences', 'due_date')) {
                $table->date('due_date')->nullable()->after('occurred_at'); // fecha de vencimiento del reporte
                $table->index(['due_date'], 'incidences_due_date_index');
            }

            // Flags para evitar duplicar notificaciones
            if (!Schema::hasColumn('incidences', 'due_today_notified_at')) {
                $table->dateTime('due_today_notified_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('incidences', 'overdue_notified_at')) {
                $table->dateTime('overdue_notified_at')->nullable()->after('due_today_notified_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('incidences')) return;

        Schema::table('incidences', function (Blueprint $table) {
            $cols = [];
            foreach (['due_date', 'due_today_notified_at', 'overdue_notified_at'] as $c) {
                if (Schema::hasColumn('incidences', $c)) $cols[] = $c;
            }
            if (count($cols)) $table->dropColumn($cols);

            try {
                $table->dropIndex('incidences_due_date_index');
            } catch (Throwable $e) {
                // ignore
            }
        });
    }
};

