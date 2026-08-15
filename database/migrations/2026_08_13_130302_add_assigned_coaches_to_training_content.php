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
        Schema::table('exercise_library_items', function (Blueprint $table) {
            $table->string('default_coach')->default('Raki')->after('created_by');
        });

        Schema::table('training_blocks', function (Blueprint $table) {
            $table->string('assigned_coach')->default('Raki')->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_blocks', function (Blueprint $table) {
            $table->dropColumn('assigned_coach');
        });

        Schema::table('exercise_library_items', function (Blueprint $table) {
            $table->dropColumn('default_coach');
        });
    }
};
