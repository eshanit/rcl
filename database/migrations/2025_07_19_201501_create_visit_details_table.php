<?php

use App\Enums\GeneralType;
use App\models\ArvSwitchReason;
use App\Models\KaposiStatus;
use App\Models\SideEffect;
use App\Models\TbStatus;
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
        Schema::create('visit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visit::class)->constrained()->cascadeOnDelete();
            $table->enum('adherent', array_map(fn ($type) => $type->value, GeneralType::cases()))->nullable()->default(GeneralType::NA->value);
            $table->string('weight')->nullable();
            $table->string('height_children')->nullable();
            $table->enum('pregnant', array_map(fn ($type) => $type->value, GeneralType::cases()))->nullable()->default(GeneralType::NA->value);
            $table->integer('tlc')->nullable();
            $table->integer('cd4')->nullable();
            $table->integer('cd4_perc')->nullable();
            $table->enum('sputum_tb_test', array_map(fn ($type) => $type->value, GeneralType::cases()))->nullable()->default(GeneralType::NA->value);
            $table->integer('alt')->nullable();
            $table->integer('viral_load')->nullable();
            $table->string('creatinine')->nullable();
            $table->string('creatinine_2')->nullable();
            $table->string('haemoglobin')->nullable();
            $table->string('arv2')->nullable();
            $table->string('arv2_name')->nullable();
            $table->foreignIdFor(ArvSwitchReason::class)->nullable()->constrained()->cascadeOnDelete()->nullOnDelete();
            $table->foreignIdFor(TBStatus::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(KaposiStatus::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_1')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->foreignId('diagnosis_2')->nullable()->constrained('diagnosis_types')->nullOnDelete();
            $table->integer('new_who_stage')->unsigned()->nullable()->check('who_stage >= 1 AND who_stage <= 4');
            $table->foreignIdFor(SideEffect::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('grupo_apoio')->nullable();
            $table->string('hbsag')->nullable();
            $table->string('ac_anti_vhc')->nullable();
            $table->string('tas')->nullable();
            $table->string('tad')->nullable();
            $table->string('plaquetas')->nullable();
            $table->string('ast_got')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_details');
    }
};
