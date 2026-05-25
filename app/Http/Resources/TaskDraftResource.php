<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDraftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'task_type' => $this->task_type,
            'title' => $this->title,
            'summary' => $this->summary,
            'priority' => $this->priority,
            'suggested_team' => $this->suggested_team,
            'confidence_score' => (float) $this->confidence_score,
            'missing_information' => $this->missing_information ?? [],
            'suggested_next_action' => $this->suggested_next_action,
            'operator_note' => $this->operator_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'incoming_email' => [
                'id' => $this->incomingEmail?->id,
                'sender' => $this->incomingEmail?->sender,
                'subject' => $this->incomingEmail?->subject,
                'body' => $this->incomingEmail?->body,
                'processing_status' => $this->incomingEmail?->processing_status,
                'failure_reason' => $this->incomingEmail?->failure_reason,
            ],
            'ai_evaluations' => $this->aiEvaluations->map(fn ($evaluation) => [
                'id' => $evaluation->id,
                'provider' => $evaluation->provider,
                'model' => $evaluation->model,
                'status' => $evaluation->status,
                'confidence_score' => $evaluation->confidence_score !== null ? (float) $evaluation->confidence_score : null,
                'error_message' => $evaluation->error_message,
                'raw_output' => $evaluation->raw_output,
                'created_at' => $evaluation->created_at?->toIso8601String(),
            ]),
            'approval_decisions' => $this->approvalDecisions->map(fn ($decision) => [
                'id' => $decision->id,
                'action' => $decision->action,
                'note' => $decision->note,
                'overrides' => $decision->overrides,
                'decided_at' => $decision->decided_at?->toIso8601String(),
            ]),
            'audit_logs' => $this->auditLogs->map(fn ($auditLog) => [
                'id' => $auditLog->id,
                'actor' => $auditLog->actor,
                'action' => $auditLog->action,
                'metadata' => $auditLog->metadata,
                'created_at' => $auditLog->created_at?->toIso8601String(),
            ]),
        ];
    }
}
