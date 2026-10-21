<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeDonor extends Model
{
    protected $table = 'initiative_donors';

    public $timestamps = false;

    protected $fillable = [
        'initiative_id',
        'donor_id',
    ];

    protected function casts(): array
    {
        return [
            'initiative_id' => 'integer',
            'donor_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Initiative, $this> */
    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class, 'initiative_id', 'id');
    }

    /** @return BelongsTo<Donor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id', 'id');
    }
}
