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
        Schema::table('weekly_checkins', function (Blueprint $table) {
            if (! Schema::hasColumn('weekly_checkins', 'protein_avg_grams')) {
                $table->unsignedSmallInteger('protein_avg_grams')->nullable()->after('protein_status');
            }

            if (! Schema::hasColumn('weekly_checkins', 'protein_target_days')) {
                $table->unsignedTinyInteger('protein_target_days')->nullable()->after('protein_avg_grams');
            }

            if (! Schema::hasColumn('weekly_checkins', 'protein_notes')) {
                $table->text('protein_notes')->nullable()->after('protein_target_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_checkins', function (Blueprint $table) {
            $columns = [
                'protein_notes',
                'protein_target_days',
                'protein_avg_grams',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('weekly_checkins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
