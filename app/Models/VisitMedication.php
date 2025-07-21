<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitMedication extends Model
{
    //
    protected $fillable = ['status'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
