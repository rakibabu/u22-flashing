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
        Schema::create('team_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('toc_status')->default('missing');
            $table->text('toc_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_documents');
    }
};
