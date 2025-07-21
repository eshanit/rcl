<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisType extends Model
{
    use HasFactory;

    //
    public function initialVisits(): HasMany
    {
        return $this->hasMany(InitialVisit::class);
    }

    public function VisitDetails(): HasMany
    {
        return $this->hasMany(VisitDetail::class);
    }
}
