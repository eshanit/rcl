<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TbStatus extends Model
{
    //
    use HasFactory;

    public function visitDetails(): HasMany
    {
        return $this->hasMany(VisitDetail::class);
    }
}
