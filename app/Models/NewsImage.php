<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsImage extends Model
{
    protected $fillable = [
        'news_id',
        'path',
        'image_order',
        'sizetype',
    ];

    protected function casts(): array
    {
        return [
            'news_id' => 'integer',
            'image_order' => 'integer',
        ];
    }

    /** @return BelongsTo<News, $this> */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }
}
