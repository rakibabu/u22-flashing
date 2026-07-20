<?php

namespace App\Models;

use Database\Factories\BasketballTrainerPlaybookLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_document_id', 'external_playbook_hash', 'external_title', 'external_updated_at', 'metadata', 'last_checked_at', 'last_error', 'linked_by_user_id'])]
#[UseFactory(BasketballTrainerPlaybookLinkFactory::class)]
class BasketballTrainerPlaybookLink extends Model
{
    /** @use HasFactory<BasketballTrainerPlaybookLinkFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'external_updated_at' => 'datetime',
            'metadata' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }

    public function teamDocument(): BelongsTo
    {
        return $this->belongsTo(TeamDocument::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by_user_id');
    }
}
