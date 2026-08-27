<?php

namespace App\Models;

use App\Models\Concerns\HasRaceSafeUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'meta_title', 'meta_description', 'published_at', 'is_published'])]
class BlogPost extends Model
{
    use HasRaceSafeUniqueSlug;

    protected const UNIQUE_SLUG_SOURCE = 'title';

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }
}
