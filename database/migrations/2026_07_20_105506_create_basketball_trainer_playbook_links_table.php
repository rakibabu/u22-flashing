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
        Schema::create('basketball_trainer_playbook_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_document_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('external_playbook_hash', 64)->index();
            $table->string('external_title');
            $table->timestamp('external_updated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->foreignId('linked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basketball_trainer_playbook_links');
    }
};
