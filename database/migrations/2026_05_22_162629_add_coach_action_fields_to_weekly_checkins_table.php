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
            if (! Schema::hasColumn('weekly_checkins', 'total_training_minutes')) {
                $table->unsignedSmallInteger('total_training_minutes')->nullable()->after('rpe_highest');
            }

            if (! Schema::hasColumn('weekly_checkins', 'highest_session_rpe')) {
                $table->unsignedTinyInteger('highest_session_rpe')->nullable()->after('total_training_minutes');
            }

            if (! Schema::hasColumn('weekly_checkins', 'calculated_training_load')) {
                $table->unsignedInteger('calculated_training_load')->nullable()->after('highest_session_rpe');
            }

            if (! Schema::hasColumn('weekly_checkins', 'missed_target_reason')) {
                $table->string('missed_target_reason')->nullable()->after('calculated_training_load');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_checkins', function (Blueprint $table) {
            $table->dropColumn([
                'total_training_minutes',
                'highest_session_rpe',
                'calculated_training_load',
                'missed_target_reason',
            ]);
        });
    }
};
