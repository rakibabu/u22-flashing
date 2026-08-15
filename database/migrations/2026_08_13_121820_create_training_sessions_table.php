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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('source_training_session_id')->nullable()->constrained('training_sessions')->nullOnDelete();
            $table->string('title');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->unsignedSmallInteger('planned_duration_minutes')->default(120);
            $table->unsignedTinyInteger('expected_player_count')->nullable();
            $table->unsignedTinyInteger('available_baskets')->nullable();
            $table->string('theme')->nullable();
            $table->text('goals')->nullable();
            $table->text('coach_notes')->nullable();
            $table->text('player_notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_template')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
