<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncomingEmail extends Model
{
    protected $fillable = [
        'sender',
        'subject',
        'body',
        'content_hash',
        'processing_status',
        'failure_reason',
    ];

    public function taskDraft(): HasOne
    {
        return $this->hasOne(TaskDraft::class);
    }

    public function aiEvaluations(): HasMany
    {
        return $this->hasMany(AiEvaluation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
