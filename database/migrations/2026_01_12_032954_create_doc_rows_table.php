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
        Schema::create('doc_rows', function (Blueprint $table) {
            $table->id();

            // Row ini milik page mana?
            $table->foreignId('page_id')
                ->constrained('doc_pages')
                ->cascadeOnDelete();

            // Konten kolom kiri
            $table->string('title')->nullable();     // subjudul row (opsional)
            $table->longText('body')->nullable();    // paragraf/penjelasan (bisa markdown)

            // Urutan row di dalam page (agar bisa diurutkan seperti GitBook)
            $table->unsignedInteger('position')->default(0);

            // Soft delete agar aman kalau row dihapus
            $table->softDeletes();
            $table->timestamps();

            // Index yang membantu query: ambil rows suatu page, urutkan
            $table->index(['page_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_rows');
    }
};
