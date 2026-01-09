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
        Schema::create('doc_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')
                ->constrained('docs_sections')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            // Format konten (kita default markdown agar aman dan mudah)
            $table->enum('content_type', ['markdown', 'html'])->default('markdown');

            // Status publikasi (internal tapi tetap butuh draft/published)
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Waktu publish (berguna untuk audit & urutkan)
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Index untuk query list pages per section
            $table->index(['section_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_pages');
    }
};
