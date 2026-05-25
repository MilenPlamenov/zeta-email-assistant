<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEvaluation extends Model
{
    protected $fillable = [
        'incoming_email_id',
        'task_draft_id',
        'provider',
        'model',
        'status',
        'confidence_score',
        'raw_output',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'raw_output' => 'array',
        ];
    }

    public function incomingEmail(): BelongsTo
    {
        return $this->belongsTo(IncomingEmail::class);
    }

    public function taskDraft(): BelongsTo
    {
        return $this->belongsTo(TaskDraft::class);
    }
}
