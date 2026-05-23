<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseLibraryItem extends Model
{
    protected $fillable = [
        'category',
        'name',
        'description',
        'execution',
        'coaching_cues',
        'common_mistakes',
        'sort_order',
    ];
}
