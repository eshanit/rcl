<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtPreExposure extends Model
{
    use HasFactory;

    //
    public function initialVisits(): HasMany
    {
        return $this->hasMany(InitialVisit::class);
    }
}
