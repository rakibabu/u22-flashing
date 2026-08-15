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
        Schema::create('training_block_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('training_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_block_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('actual_duration_seconds')->default(0);
            $table->unsignedInteger('added_duration_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['training_run_id', 'training_block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_block_runs');
    }
};
