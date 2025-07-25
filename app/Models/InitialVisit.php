<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialVisit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'first_positive_hiv',
        'who_stage',
        'diagnosis_1',
        'diagnosis_2',
        'diagnosis_3',
        'diagnosis_4',
        'art_pre_exposure_id',
        'previous_tb_tt',
        'art_start_place_id',
        'art_start_date',
        'cd4_baseline',
    ];

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
