<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramPhase extends Model
{
    protected $fillable = ['program_template_id', 'name', 'start_date', 'end_date', 'description', 'sort_order'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function programTemplate(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class);
    }
}
