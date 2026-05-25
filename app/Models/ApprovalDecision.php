<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDecision extends Model
{
    protected $fillable = [
        'task_draft_id',
        'action',
        'note',
        'overrides',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'overrides' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function taskDraft(): BelongsTo
    {
        return $this->belongsTo(TaskDraft::class);
    }
}
