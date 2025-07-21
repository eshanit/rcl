<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    use HasFactory;

    //
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
