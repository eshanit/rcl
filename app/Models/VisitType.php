<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitType extends Model
{
    //
    use HasFactory;

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
