<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'category';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Name',
    ];

    public function projectSubCategories(): HasMany
    {
        return $this->hasMany(ProjectSubCategory::class, 'Category_ID', 'ID');
    }
}
