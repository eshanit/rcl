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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // access, modify, delete, export, etc.
            $table->string('resource_type'); // patient, visit, report, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('old_values')->nullable(); // For tracking changes
            $table->json('new_values')->nullable(); // For tracking changes
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('reason')->nullable(); // Business reason for access
            $table->timestamp('accessed_at');
            $table->timestamps();

            $table->index(['user_id', 'accessed_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('action');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
