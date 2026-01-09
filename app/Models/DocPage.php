<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocPage extends Model
{
    protected $table = 'doc_pages';

    protected $fillable = [
        'section_id',
        'title',
        'slug',
        'description',
        'content',
        'content_type',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(DocSection::class, 'section_id');
    }

    public function snippets(): HasMany
    {
        return $this->hasMany(DocCodeSnippet::class, 'page_id')
            ->orderBy('position');
    }
}
