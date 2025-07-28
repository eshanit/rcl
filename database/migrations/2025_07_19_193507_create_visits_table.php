<?php

use App\Models\Facility;
use App\Models\Patient;
use App\Models\TransferType;
use App\Models\VisitType;
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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Facility::class)->constrained()->cascadeOnDelete();
            $table->integer('instance');
            $table->foreignIdFor(VisitType::class)->constrained()->cascadeOnDelete();
            $table->date('app_visit_date')->nullable();
            $table->date('actual_visit_date')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->boolean('transfer_smart')->nullable();
            $table->foreignIdFor(TransferType::class)->nullable()->constrained()->nullOnDelete();
            $table->string('batch_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
