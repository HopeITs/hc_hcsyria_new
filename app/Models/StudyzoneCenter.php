<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyzoneCenter extends Model
{
    protected $table = 'studyzonecenters';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'start_date',
        'work_hours',
        'address',
        'name_fr',
        'work_hours_fr',
        'address_fr',
        'location',
        'telephone',
        'email',
        'facebook',
        'instagram',
        'active',
        'latitude',
        'longtitude',
        'place_id',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'integer',
            'place_id' => 'integer',
            'rank' => 'integer',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id', 'id');
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'sz_center_id', 'id');
    }

    public function studentEntries(): HasMany
    {
        return $this->hasMany(StudentEntry::class, 'center_id', 'id');
    }

    public function successes(): HasMany
    {
        return $this->hasMany(Success::class, 'sz_center_id', 'id');
    }

    public function userCenters(): HasMany
    {
        return $this->hasMany(UserCenter::class, 'studyzone_center_id', 'id');
    }
}
