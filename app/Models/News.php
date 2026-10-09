<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class News extends Model
{
    protected $fillable = [
        'rank',
        'type',
        'video',
        'file',
        'english_check',
        'online',
        'hidden',
        'what_category_id',
        'who_category_id',
        'place_id',
        'edition_id',
        'date',
        'priority_status',
        'view_count',
        'total_views',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'type' => 'integer',
            'english_check' => 'integer',
            'online' => 'integer',
            'hidden' => 'boolean',
            'what_category_id' => 'integer',
            'who_category_id' => 'integer',
            'place_id' => 'integer',
            'edition_id' => 'integer',
            'date' => 'date',
            'view_count' => 'integer',
            'total_views' => 'integer',
        ];
    }

    /** @return BelongsTo<Place, $this> */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id', 'id');
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'id');
    }

    /** @return HasOne<NewsContent, $this> */
    public function content(): HasOne
    {
        return $this->hasOne(NewsContent::class, 'news_id', 'id');
    }

    /** @return HasMany<NewsImage, $this> */
    public function newsImages(): HasMany
    {
        return $this->hasMany(NewsImage::class, 'news_id', 'id');
    }

    /** @return HasMany<NewsTag, $this> */
    public function newsTags(): HasMany
    {
        return $this->hasMany(NewsTag::class, 'news_id', 'id');
    }
}
