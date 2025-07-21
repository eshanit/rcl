<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDetail extends Model
{
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
