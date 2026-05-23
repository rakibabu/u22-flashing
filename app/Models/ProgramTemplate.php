<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTemplate extends Model
{
    protected $fillable = ['type', 'name', 'description', 'goal', 'sort_order'];

    public function phases(): HasMany
    {
        return $this->hasMany(ProgramPhase::class)->orderBy('sort_order');
    }
}
