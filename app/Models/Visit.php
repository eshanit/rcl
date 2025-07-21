<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    //
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function visitType(): BelongsTo
    {
        return $this->belongsTo(VisitType::class);
    }

    public function transferType(): BelongsTo
    {
        return $this->belongsTo(TransferType::class);
    }

    public function visitDetails(): HasMany
    {
        return $this->hasMany(visitDetail::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(VisitMedication::class);
    }
}
