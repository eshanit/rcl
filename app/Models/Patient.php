<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    use HasFactory;
    //

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // public function facility(): BelongsTo
    // {
    //     return $this->belongsTo(Facility::class);
    // }

    public function initialVisit(): HasOne
    {
        return $this->hasOne(InitialVisit::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(PatientStatus::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
