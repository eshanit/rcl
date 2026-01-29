<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->index(['patient_id', 'actual_visit_date']);
            $table->index(['next_visit_date']);
            $table->index(['actual_visit_date']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index(['patient_status_id']);
        });

        Schema::table('initial_visits', function (Blueprint $table) {
            $table->index(['art_start_date']);
            $table->index(['patient_id', 'art_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex(['patient_id', 'actual_visit_date']);
            $table->dropIndex(['next_visit_date']);
            $table->dropIndex(['actual_visit_date']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['patient_status_id']);
        });

        Schema::table('initial_visits', function (Blueprint $table) {
            $table->dropIndex(['art_start_date']);
            $table->dropIndex(['patient_id', 'art_start_date']);
        });
    }
};
