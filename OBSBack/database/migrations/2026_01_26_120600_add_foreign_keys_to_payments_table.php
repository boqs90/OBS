<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Add FKs only if referenced tables exist.
        if (Schema::hasTable('students')) {
            $dbName = DB::getDatabaseName();
            $hasFkStudent = DB::select(
                "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payments' AND CONSTRAINT_NAME = 'payments_student_id_foreign' LIMIT 1",
                [$dbName]
            );

            if (empty($hasFkStudent)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreign('student_id', 'payments_student_id_foreign')
                        ->references('id')
                        ->on('students')
                        ->onDelete('cascade');
                });
            }
        }

        if (Schema::hasTable('payment_concepts')) {
            $dbName = DB::getDatabaseName();
            $hasFkConcept = DB::select(
                "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payments' AND CONSTRAINT_NAME = 'payments_payment_concept_id_foreign' LIMIT 1",
                [$dbName]
            );

            if (empty($hasFkConcept)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreign('payment_concept_id', 'payments_payment_concept_id_foreign')
                        ->references('id')
                        ->on('payment_concepts')
                        ->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropForeign('payments_student_id_foreign');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign('payments_payment_concept_id_foreign');
            } catch (\Throwable $e) {
            }
        });
    }
};
