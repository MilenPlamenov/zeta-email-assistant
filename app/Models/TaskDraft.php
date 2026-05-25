<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskDraft extends Model
{
    protected $fillable = [
        'incoming_email_id',
        'status',
        'task_type',
        'title',
        'summary',
        'priority',
        'suggested_team',
        'confidence_score',
        'missing_information',
        'suggested_next_action',
        'operator_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'missing_information' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function incomingEmail(): BelongsTo
    {
        return $this->belongsTo(IncomingEmail::class);
    }

    public function aiEvaluations(): HasMany
    {
        return $this->hasMany(AiEvaluation::class);
    }

    public function approvalDecisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
