<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsContent extends Model
{
    protected $table = 'news_english';

    protected $fillable = [
        'news_id',
        'title',
        'description',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'news_id' => 'integer',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }
}
