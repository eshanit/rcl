<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'visit_id',
        'adherent',
        'weight',
        'height_children',
        'pregnant',
        'tlc',
        'sputum_tb_test',
        'alt',
        'creatinine',
        'creatinine2',
        'haemoglobin',
        'arv2',
        'arv2_name',
        'arv_switch_reason_id',
        'tb_status_id',
        'kaposi_status_id',
        'diagnosis_1',
        'diagnosis_2',
        'new_who_stage',
        'side_effect_id',
        'grupo_apoio',
        'hbsag',
        'ac_anti_vhc',
        'tas',
        'tad',
        'plaquetas',
        'ast_got',
    ];

    //
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function arvSwitchDetail(): BelongsTo
    {
        return $this->belongsTo(ArvSwitchDetail::class);
    }

    public function tbStatus(): BelongsTo
    {
        return $this->belongsTo(TbStatus::class);
    }

    public function kaposiStatus(): BelongsTo
    {
        return $this->belongsTo(KaposiStatus::class);
    }

    public function diagnosisType()
    {
        return $this->belongsTo(DiagnosisType::class);
    }

    public function sideEffect()
    {
        return $this->belongsTo(SideEffect::class);
    }
}
