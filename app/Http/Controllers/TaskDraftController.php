<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskDraftResource;
use App\Models\TaskDraft;

class TaskDraftController extends Controller
{
    public function show(TaskDraft $taskDraft): TaskDraftResource
    {
        return new TaskDraftResource($taskDraft->load([
            'incomingEmail',
            'aiEvaluations',
            'approvalDecisions',
            'auditLogs',
        ]));
    }
}
