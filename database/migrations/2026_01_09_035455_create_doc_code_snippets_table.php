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
        Schema::create('doc_code_snippets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')
                ->constrained('doc_pages')
                ->cascadeOnDelete();
            $table->enum('language', ['bash', 'javascript', 'php', 'python']);
            $table->string('title')->nullable();
            $table->longText('code');

            // Urutan snippet dalam satu page (kalau Anda punya lebih dari 1 snippet per language)
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            // Index untuk query cepat: ambil snippet per page, per language
            $table->index(['page_id', 'language', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_code_snippets');
    }
};
