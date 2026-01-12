<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocRowSnippet extends Model
{
    use SoftDeletes;

    protected $table = 'doc_row_snippets';

    protected $fillable = [
        'row_id',
        'language',
        'code',
        'position',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(DocRow::class, 'row_id');
    }
}
