<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facility extends Model
{
    use HasFactory;

    //
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
