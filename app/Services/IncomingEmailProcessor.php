<?php

namespace App\Services;

use App\Exceptions\AiProcessingException;
use App\Exceptions\DuplicateEmailException;
use App\Models\AiEvaluation;
use App\Models\IncomingEmail;
use App\Models\TaskDraft;
use App\Services\AI\EmailTaskInterpreter;
use Illuminate\Support\Facades\DB;

class IncomingEmailProcessor
{
    public function __construct(
        private readonly EmailTaskInterpreter $interpreter,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function process(string $sender, string $subject, string $body): TaskDraft
    {
        $hash = hash('sha256', mb_strtolower(trim($sender)).'|'.trim($subject).'|'.trim($body));

        if (IncomingEmail::query()->where('content_hash', $hash)->exists()) {
            throw new DuplicateEmailException('This email has already been ingested.');
        }

        $incomingEmail = IncomingEmail::create([
            'sender' => $sender,
            'subject' => $subject,
            'body' => $body,
            'content_hash' => $hash,
            'processing_status' => 'processing',
        ]);

        $this->auditLogger->log(
            action: 'incoming_email.received',
            incomingEmail: $incomingEmail,
            metadata: ['sender' => $sender, 'subject' => $subject],
        );

        try {
            $interpretation = $this->interpreter->interpret($sender, $subject, $body);

            return DB::transaction(function () use ($incomingEmail, $interpretation) {
                $taskDraft = $incomingEmail->taskDraft()->create([
                    ...$interpretation->toTaskDraftAttributes(),
                    'status' => 'pending_review',
                ]);

                AiEvaluation::create([
                    'incoming_email_id' => $incomingEmail->id,
                    'task_draft_id' => $taskDraft->id,
                    'provider' => $interpretation->provider,
                    'model' => $interpretation->model,
                    'status' => 'success',
                    'confidence_score' => $interpretation->confidenceScore,
                    'raw_output' => $interpretation->rawOutput,
                ]);

                $incomingEmail->update(['processing_status' => 'processed']);

                $this->auditLogger->log(
                    action: 'task_draft.generated',
                    incomingEmail: $incomingEmail,
                    taskDraft: $taskDraft,
                    metadata: [
                        'task_type' => $taskDraft->task_type,
                        'confidence_score' => $taskDraft->confidence_score,
                    ],
                );

                return $taskDraft->fresh(['incomingEmail', 'aiEvaluations', 'approvalDecisions', 'auditLogs']);
            });
        } catch (AiProcessingException $exception) {
            AiEvaluation::create([
                'incoming_email_id' => $incomingEmail->id,
                'provider' => 'mock-ai',
                'model' => 'rules-v1',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            $incomingEmail->update([
                'processing_status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            $this->auditLogger->log(
                action: 'incoming_email.ai_failed',
                incomingEmail: $incomingEmail,
                metadata: ['error' => $exception->getMessage()],
            );

            throw $exception;
        }
    }
}
