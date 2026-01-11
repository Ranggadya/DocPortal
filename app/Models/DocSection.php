<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class DocSection extends Model
{
    use SoftDeletes;
    protected $table = 'docs_sections';

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'position',
        'is_active',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocSection::class, 'parent_id');
    }

    public function childern(): HasMany
    {
        return $this->hasMany(DocSection::class, 'parent_id')
            ->orderBy('position');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocPage::class, 'section_id')
            ->orderBy('title');
    }
}
