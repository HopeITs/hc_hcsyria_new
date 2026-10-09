<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSubCategory extends Model
{
    protected $table = 'subcategory';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'Name',
        'Category_ID',
    ];

    protected function casts(): array
    {
        return [
            'Category_ID' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function categoryID(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'Category_ID', 'ID');
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'SubCategory_ID', 'ID');
    }
}
