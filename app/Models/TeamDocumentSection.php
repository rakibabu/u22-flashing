<?php

namespace App\Models;

use Database\Factories\TeamDocumentSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_document_id', 'title', 'page_number', 'sort_order', 'source'])]
#[UseFactory(TeamDocumentSectionFactory::class)]
class TeamDocumentSection extends Model
{
    /** @use HasFactory<TeamDocumentSectionFactory> */
    use HasFactory;

    public function teamDocument(): BelongsTo
    {
        return $this->belongsTo(TeamDocument::class);
    }
}
