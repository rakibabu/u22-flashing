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
        Schema::create('exercise_library_items', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('name');
            $table->text('description');
            $table->text('execution');
            $table->text('coaching_cues');
            $table->text('common_mistakes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_library_items');
    }
};
