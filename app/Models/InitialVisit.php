<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialVisit extends Model
{
    use HasFactory;

    //
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function artPreExposure()
    {
        return $this->belongsTo(ArtPreExposure::class);
    }

    public function artStartPlace()
    {
        return $this->belongsTo(ArtStartPlace::class);
    }

    public function diagnosisType()
    {
        return $this->belongsTo(DiagnosisType::class);
    }
}
