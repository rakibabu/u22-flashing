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
        Schema::create('training_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('unknown');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['training_run_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_attendances');
    }
};
