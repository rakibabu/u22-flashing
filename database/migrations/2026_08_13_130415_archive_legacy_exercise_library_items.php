<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('exercise_library_items')
            ->whereNull('created_by')
            ->whereNull('archived_at')
            ->update(['archived_at' => now(), 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('exercise_library_items')
            ->whereNull('created_by')
            ->update(['archived_at' => null, 'updated_at' => now()]);
    }
};
