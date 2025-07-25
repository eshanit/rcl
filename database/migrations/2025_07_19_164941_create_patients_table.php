<?php

use App\Enums\GenderType;
use App\Models\Site; // Ensure you import your GenderType enum
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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('p_number')->unique();
            $table->string('np_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', array_map(fn ($gender) => $gender->value, GenderType::cases()))->default(GenderType::UNKNOWN->value);
            $table->integer('height')->unsigned()->nullable()->check('height >= 0 AND height <= 250');
            $table->foreignIdFor(Site::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
