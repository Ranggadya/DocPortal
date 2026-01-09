<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocCodeSnippet extends Model
{
    protected $table = 'doc_code_snippets';

    protected $fillable = [
        'page_id',
        'language',
        'title',
        'code',
        'position',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(DocPage::class, 'page_id');
    }
}
