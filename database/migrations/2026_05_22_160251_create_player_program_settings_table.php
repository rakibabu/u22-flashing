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
        Schema::create('player_program_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('strength_target_per_week')->default(2);
            $table->unsignedTinyInteger('conditioning_target_per_week')->default(2);
            $table->unsignedTinyInteger('mobility_target_per_week')->default(3);
            $table->unsignedSmallInteger('kcal_rest_day')->nullable();
            $table->unsignedSmallInteger('kcal_training_day')->nullable();
            $table->unsignedSmallInteger('kcal_pickup_day')->nullable();
            $table->unsignedSmallInteger('kcal_minimum')->nullable();
            $table->unsignedSmallInteger('protein_target_min')->nullable();
            $table->unsignedSmallInteger('protein_target_max')->nullable();
            $table->boolean('pickup_monday_expected')->default(false);
            $table->boolean('pickup_thursday_expected')->default(false);
            $table->boolean('uses_mijn_eetmeter')->default(false);
            $table->boolean('uses_yazio_backup')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_program_settings');
    }
};
