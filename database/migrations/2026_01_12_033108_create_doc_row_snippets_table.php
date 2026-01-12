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
        Schema::create('doc_row_snippets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('row_id')
                ->constrained('doc_rows')
                ->cascadeOnDelete();
            $table->string('language', 32);

            $table->longText('code');
            $table->unsignedInteger('position')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['row_id', 'position']);
            $table->unique(['row_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_row_snippets');
    }
};
