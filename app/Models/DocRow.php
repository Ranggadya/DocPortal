<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocRow extends Model
{
    use SoftDeletes;
    protected $table = 'doc_row';
    protected $fillable = [
        'page_id',
        'title',
        'body',
        'position',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(DocPage::class, 'page_id');
    }

    public function snippet(): HasMany
    {
        return $this->hasMany(DocRowSnippet::class, 'row_id')
        ->orderBy('position')
        ->orderBy('id');
    }

}
