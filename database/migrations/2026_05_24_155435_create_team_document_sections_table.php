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
        Schema::create('team_document_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_document_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('page_number')->default(1);
            $table->unsignedSmallInteger('sort_order');
            $table->string('source')->default('text');
            $table->timestamps();

            $table->index(['team_document_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_document_sections');
    }
};
