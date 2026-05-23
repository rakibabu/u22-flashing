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
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('test_date');
            $table->decimal('body_weight_kg', 5, 2)->nullable();
            $table->decimal('sprint_20m_seconds', 5, 2)->nullable();
            $table->decimal('repeated_sprint_total_seconds', 6, 2)->nullable();
            $table->decimal('repeated_sprint_dropoff_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('five_min_run_meters')->nullable();
            $table->decimal('agility_5_10_5_seconds', 5, 2)->nullable();
            $table->string('yo_yo_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
