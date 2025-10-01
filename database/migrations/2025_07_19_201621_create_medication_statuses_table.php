<?php

use App\Enums\MedicationStatusType;
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
        Schema::create('medication_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('name', array_map(fn ($type) => $type->value, MedicationStatusType::cases()))->nullable()->default(MedicationStatusType::NOT_APPLICABLE->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_statuses');
    }
};
