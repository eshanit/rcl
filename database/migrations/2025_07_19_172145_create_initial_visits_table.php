<?php

use App\Enums\GeneralType;
use App\Models\ArtPreExposure;
use App\Models\ArtStartPlace;
use App\Models\Patient;
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
        Schema::create('initial_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained()->cascadeOnDelete();
            $table->date('first_positive_hiv')->nullable();
            $table->integer('who_stage')->unsigned()->nullable()->check('who_stage >= 1 AND who_stage <= 4');
            $table->foreignId('diagnosis_1')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->foreignId('diagnosis_2')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->foreignId('diagnosis_3')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->foreignId('diagnosis_4')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->foreignIdFor(ArtPreExposure::class)->constrained()->cascadeOnDelete();
            $table->enum('previous_tb_tt', array_map(fn ($type) => $type->value, GeneralType::cases()))->default(GeneralType::NA->value);
            $table->foreignIdFor(ArtStartPlace::class)->constrained()->cascadeOnDelete();
            $table->date('art_start_date');
            $table->string('cd4_baseline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_visits');
    }
};
