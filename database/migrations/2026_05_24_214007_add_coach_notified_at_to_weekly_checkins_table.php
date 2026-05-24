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
            if (! Schema::hasColumn('weekly_checkins', 'coach_notified_at')) {
                $table->timestamp('coach_notified_at')->nullable()->after('submitted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_checkins', function (Blueprint $table) {
            if (Schema::hasColumn('weekly_checkins', 'coach_notified_at')) {
                $table->dropColumn('coach_notified_at');
            }
        });
    }
};
