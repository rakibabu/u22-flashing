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
        Schema::create('training_run_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_run_id')->constrained()->cascadeOnDelete();
            $table->string('author_name', 100);
            $table->text('feedback');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_run_feedback');
    }
};
