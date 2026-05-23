<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('weekly_checkins', function (Blueprint $table) {
            $table->unsignedTinyInteger('strength_sessions')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('conditioning_sessions')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('mobility_sessions')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('weekly_checkins')->whereNull('strength_sessions')->update(['strength_sessions' => 0]);
        DB::table('weekly_checkins')->whereNull('conditioning_sessions')->update(['conditioning_sessions' => 0]);
        DB::table('weekly_checkins')->whereNull('mobility_sessions')->update(['mobility_sessions' => 0]);

        Schema::table('weekly_checkins', function (Blueprint $table) {
            $table->unsignedTinyInteger('strength_sessions')->default(0)->nullable(false)->change();
            $table->unsignedTinyInteger('conditioning_sessions')->default(0)->nullable(false)->change();
            $table->unsignedTinyInteger('mobility_sessions')->default(0)->nullable(false)->change();
        });
    }
};
