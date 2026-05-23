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
            if (! Schema::hasColumn('weekly_checkins', 'missed_target_reason_other')) {
                $table->string('missed_target_reason_other')->nullable()->after('missed_target_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_checkins', function (Blueprint $table) {
            if (Schema::hasColumn('weekly_checkins', 'missed_target_reason_other')) {
                $table->dropColumn('missed_target_reason_other');
            }
        });
    }
};
