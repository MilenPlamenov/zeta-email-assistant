<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\IncomingEmail;
use App\Models\TaskDraft;

class AuditLogger
{
    public function log(
        string $action,
        string $actor = 'system',
        ?IncomingEmail $incomingEmail = null,
        ?TaskDraft $taskDraft = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'incoming_email_id' => $incomingEmail?->id,
            'task_draft_id' => $taskDraft?->id,
            'actor' => $actor,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
