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
        Schema::table('player_program_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('player_program_settings', 'handle_sessions_target_per_week')) {
                $table->unsignedTinyInteger('handle_sessions_target_per_week')->nullable()->after('mobility_target_per_week');
            }

            if (! Schema::hasColumn('player_program_settings', 'handle_minutes_target_per_week')) {
                $table->unsignedSmallInteger('handle_minutes_target_per_week')->nullable()->after('handle_sessions_target_per_week');
            }

            if (! Schema::hasColumn('player_program_settings', 'pickup_target_per_week')) {
                $table->unsignedTinyInteger('pickup_target_per_week')->nullable()->after('handle_minutes_target_per_week');
            }

            if (! Schema::hasColumn('player_program_settings', 'conditioning_minutes_target_per_week')) {
                $table->unsignedSmallInteger('conditioning_minutes_target_per_week')->nullable()->after('pickup_target_per_week');
            }

            if (! Schema::hasColumn('player_program_settings', 'defence_sessions_target_per_week')) {
                $table->unsignedTinyInteger('defence_sessions_target_per_week')->nullable()->after('conditioning_minutes_target_per_week');
            }

            if (! Schema::hasColumn('player_program_settings', 'playbook_calls_target_per_week')) {
                $table->unsignedTinyInteger('playbook_calls_target_per_week')->nullable()->after('defence_sessions_target_per_week');
            }
        });

        Schema::table('weekly_checkins', function (Blueprint $table) {
            if (! Schema::hasColumn('weekly_checkins', 'handle_sessions')) {
                $table->unsignedTinyInteger('handle_sessions')->nullable()->after('mobility_sessions');
            }

            if (! Schema::hasColumn('weekly_checkins', 'handle_minutes')) {
                $table->unsignedSmallInteger('handle_minutes')->nullable()->after('handle_sessions');
            }

            if (! Schema::hasColumn('weekly_checkins', 'handles_worked_on')) {
                $table->text('handles_worked_on')->nullable()->after('handle_minutes');
            }

            if (! Schema::hasColumn('weekly_checkins', 'pickup_sessions')) {
                $table->unsignedTinyInteger('pickup_sessions')->nullable()->after('pickup_thursday');
            }

            if (! Schema::hasColumn('weekly_checkins', 'conditioning_minutes')) {
                $table->unsignedSmallInteger('conditioning_minutes')->nullable()->after('total_training_minutes');
            }

            if (! Schema::hasColumn('weekly_checkins', 'defence_sessions')) {
                $table->unsignedTinyInteger('defence_sessions')->nullable()->after('conditioning_minutes');
            }

            if (! Schema::hasColumn('weekly_checkins', 'playbook_calls_learned')) {
                $table->unsignedTinyInteger('playbook_calls_learned')->nullable()->after('defence_sessions');
            }

            if (! Schema::hasColumn('weekly_checkins', 'playbook_focus')) {
                $table->text('playbook_focus')->nullable()->after('playbook_calls_learned');
            }

            if (! Schema::hasColumn('weekly_checkins', 'attendance_notes')) {
                $table->text('attendance_notes')->nullable()->after('playbook_focus');
            }

            if (! Schema::hasColumn('weekly_checkins', 'absence_communication_notes')) {
                $table->text('absence_communication_notes')->nullable()->after('attendance_notes');
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
                'absence_communication_notes',
                'attendance_notes',
                'playbook_focus',
                'playbook_calls_learned',
                'defence_sessions',
                'conditioning_minutes',
                'pickup_sessions',
                'handles_worked_on',
                'handle_minutes',
                'handle_sessions',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('weekly_checkins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('player_program_settings', function (Blueprint $table) {
            $columns = [
                'playbook_calls_target_per_week',
                'defence_sessions_target_per_week',
                'conditioning_minutes_target_per_week',
                'pickup_target_per_week',
                'handle_minutes_target_per_week',
                'handle_sessions_target_per_week',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('player_program_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
