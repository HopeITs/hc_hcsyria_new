<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'news_id',
        'tag_id',
    ];

    protected function casts(): array
    {
        return [
            'news_id' => 'integer',
            'tag_id' => 'integer',
        ];
    }

    /** @return BelongsTo<News, $this> */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }

    /** @return BelongsTo<Tag, $this> */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id', 'id');
    }
}
