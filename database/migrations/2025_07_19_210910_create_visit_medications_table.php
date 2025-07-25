<?php

use App\Models\Medication;
use App\Models\MedicationStatus;
use App\Models\Visit;
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
        Schema::create('visit_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visit::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MedicationStatus::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Medication::class)->constrained()->cascadeOnDelete();
            $table->unique(['visit_id', 'medication_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_medications');
    }
};
