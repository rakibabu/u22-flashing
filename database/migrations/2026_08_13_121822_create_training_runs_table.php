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
        Schema::create('training_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('current_training_block_id')->nullable()->constrained('training_blocks')->nullOnDelete();
            $table->string('status')->default('in_progress')->index();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->unsignedInteger('total_paused_seconds')->default(0);
            $table->text('general_notes')->nullable();
            $table->text('what_worked')->nullable();
            $table->text('what_to_change')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_runs');
    }
};
