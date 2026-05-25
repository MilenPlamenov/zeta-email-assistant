<?php

namespace App\Services;

use App\Exceptions\TaskDraftStateException;
use App\Models\ApprovalDecision;
use App\Models\TaskDraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TaskDraftReviewService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function approve(TaskDraft $taskDraft, ?string $note): TaskDraft
    {
        if ($taskDraft->status === 'approved') {
            throw new TaskDraftStateException('This task draft has already been approved.');
        }

        if ($taskDraft->status === 'rejected') {
            throw new TaskDraftStateException('Rejected task drafts cannot be approved.');
        }

        return DB::transaction(function () use ($taskDraft, $note) {
            $reviewedAt = Carbon::now();

            $taskDraft->update([
                'status' => 'approved',
                'operator_note' => $note,
                'reviewed_at' => $reviewedAt,
            ]);

            ApprovalDecision::create([
                'task_draft_id' => $taskDraft->id,
                'action' => 'approved',
                'note' => $note,
                'decided_at' => $reviewedAt,
            ]);

            $this->auditLogger->log(
                action: 'task_draft.approved',
                actor: 'operator',
                incomingEmail: $taskDraft->incomingEmail,
                taskDraft: $taskDraft,
                metadata: ['note' => $note],
            );

            return $taskDraft->fresh(['incomingEmail', 'aiEvaluations', 'approvalDecisions', 'auditLogs']);
        });
    }

    public function reject(TaskDraft $taskDraft, ?string $note): TaskDraft
    {
        if ($taskDraft->status === 'approved') {
            throw new TaskDraftStateException('Approved task drafts cannot be rejected.');
        }

        if ($taskDraft->status === 'rejected') {
            throw new TaskDraftStateException('This task draft has already been rejected.');
        }

        return DB::transaction(function () use ($taskDraft, $note) {
            $reviewedAt = Carbon::now();

            $taskDraft->update([
                'status' => 'rejected',
                'operator_note' => $note,
                'reviewed_at' => $reviewedAt,
            ]);

            ApprovalDecision::create([
                'task_draft_id' => $taskDraft->id,
                'action' => 'rejected',
                'note' => $note,
                'decided_at' => $reviewedAt,
            ]);

            $this->auditLogger->log(
                action: 'task_draft.rejected',
                actor: 'operator',
                incomingEmail: $taskDraft->incomingEmail,
                taskDraft: $taskDraft,
                metadata: ['note' => $note],
            );

            return $taskDraft->fresh(['incomingEmail', 'aiEvaluations', 'approvalDecisions', 'auditLogs']);
        });
    }

    public function override(TaskDraft $taskDraft, array $changes, string $reason): TaskDraft
    {
        if ($taskDraft->status === 'approved') {
            throw new TaskDraftStateException('Approved task drafts cannot be overridden.');
        }

        if ($taskDraft->status === 'rejected') {
            throw new TaskDraftStateException('Rejected task drafts cannot be overridden.');
        }

        return DB::transaction(function () use ($taskDraft, $changes, $reason) {
            $reviewedAt = Carbon::now();
            $taskDraft->fill($changes);
            $taskDraft->status = 'overridden';
            $taskDraft->operator_note = $reason;
            $taskDraft->reviewed_at = $reviewedAt;
            $taskDraft->save();

            ApprovalDecision::create([
                'task_draft_id' => $taskDraft->id,
                'action' => 'overridden',
                'note' => $reason,
                'overrides' => $changes,
                'decided_at' => $reviewedAt,
            ]);

            $this->auditLogger->log(
                action: 'task_draft.overridden',
                actor: 'operator',
                incomingEmail: $taskDraft->incomingEmail,
                taskDraft: $taskDraft,
                metadata: ['reason' => $reason, 'changes' => $changes],
            );

            return $taskDraft->fresh(['incomingEmail', 'aiEvaluations', 'approvalDecisions', 'auditLogs']);
        });
    }
}
