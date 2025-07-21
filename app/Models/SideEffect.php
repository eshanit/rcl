<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SideEffect extends Model
{
    //
    //
    use HasFactory;

    public function VisitDetails(): HasMany
    {
        return $this->hasMany(VisitDetail::class);
    }
}
