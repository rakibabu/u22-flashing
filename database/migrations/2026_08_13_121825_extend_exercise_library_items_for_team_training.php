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
            $table->string('scope')->default('both')->after('category')->index();
            $table->text('objective')->nullable()->after('description');
            $table->text('organization')->nullable()->after('objective');
            $table->unsignedSmallInteger('default_duration_minutes')->nullable()->after('execution');
            $table->unsignedTinyInteger('min_players')->nullable()->after('default_duration_minutes');
            $table->unsignedTinyInteger('max_players')->nullable()->after('min_players');
            $table->unsignedTinyInteger('baskets_required')->nullable()->after('max_players');
            $table->string('intensity')->nullable()->after('baskets_required');
            $table->json('materials')->nullable()->after('intensity');
            $table->json('coaching_points')->nullable()->after('materials');
            $table->json('constraints')->nullable()->after('coaching_points');
            $table->json('regressions')->nullable()->after('constraints');
            $table->json('progressions')->nullable()->after('regressions');
            $table->json('tags')->nullable()->after('progressions');
            $table->text('coach_notes')->nullable()->after('tags');
            $table->string('media_path')->nullable()->after('coach_notes');
            $table->string('media_type')->nullable()->after('media_path');
            $table->string('video_url')->nullable()->after('media_type');
            $table->string('external_url')->nullable()->after('video_url');
            $table->foreignId('created_by')->nullable()->after('external_url')->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->after('created_by')->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercise_library_items', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropSoftDeletes();
            $table->dropColumn(['scope', 'objective', 'organization', 'default_duration_minutes', 'min_players', 'max_players', 'baskets_required', 'intensity', 'materials', 'coaching_points', 'constraints', 'regressions', 'progressions', 'tags', 'coach_notes', 'media_path', 'media_type', 'video_url', 'external_url', 'created_by', 'archived_at']);
        });
    }
};
