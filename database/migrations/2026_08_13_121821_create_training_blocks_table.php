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
        Schema::create('training_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_library_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('block_type')->default('exercise');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title');
            $table->unsignedSmallInteger('planned_duration_minutes');
            $table->json('exercise_snapshot')->nullable();
            $table->text('coach_notes')->nullable();
            $table->text('player_notes')->nullable();
            $table->text('grouping_notes')->nullable();
            $table->text('transition_notes')->nullable();
            $table->timestamps();
            $table->unique(['training_session_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_blocks');
    }
};
