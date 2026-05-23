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
        Schema::create('weekly_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('week_start_date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->unsignedTinyInteger('strength_sessions')->default(0);
            $table->unsignedTinyInteger('conditioning_sessions')->default(0);
            $table->unsignedTinyInteger('mobility_sessions')->default(0);
            $table->boolean('pickup_monday')->nullable();
            $table->boolean('pickup_thursday')->nullable();
            $table->decimal('sleep_avg_hours', 4, 2)->nullable();
            $table->unsignedTinyInteger('energy_score')->nullable();
            $table->unsignedTinyInteger('soreness_score')->nullable();
            $table->boolean('pain')->default(false);
            $table->string('pain_location')->nullable();
            $table->text('pain_notes')->nullable();
            $table->unsignedTinyInteger('rpe_highest')->nullable();
            $table->unsignedSmallInteger('kcal_avg')->nullable();
            $table->string('protein_status')->nullable();
            $table->unsignedTinyInteger('appetite_score')->nullable();
            $table->boolean('used_mijn_eetmeter')->nullable();
            $table->boolean('used_yazio')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'week_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_checkins');
    }
};
