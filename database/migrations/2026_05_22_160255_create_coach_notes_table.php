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
        Schema::create('coach_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('week_start_date')->nullable();
            $table->string('type')->default('advice')->index();
            $table->string('title');
            $table->text('body');
            $table->boolean('visible_to_player')->default(false)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coach_notes');
    }
};
