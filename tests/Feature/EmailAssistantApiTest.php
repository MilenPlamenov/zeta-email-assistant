<?php

namespace Tests\Feature;

use App\Models\ApprovalDecision;
use App\Models\IncomingEmail;
use App\Models\TaskDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAssistantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ingests_an_email_and_generates_a_task_draft(): void
    {
        $response = $this->postJson('/api/incoming-emails', [
            'sender' => 'client@example.com',
            'subject' => 'Checkout error on production',
            'body' => 'Customers report a broken checkout flow. Steps to reproduce are attached and the error appears on version 3.4.2.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.task_type', 'bug_report')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.incoming_email.processing_status', 'processed');

        $this->assertDatabaseCount('incoming_emails', 1);
        $this->assertDatabaseCount('task_drafts', 1);
        $this->assertDatabaseHas('ai_evaluations', [
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task_draft.generated',
        ]);
    }

    public function test_it_rejects_duplicate_emails(): void
    {
        $payload = [
            'sender' => 'client@example.com',
            'subject' => 'Feature request',
            'body' => 'We need bulk export because the support team handles large partner reports every Friday.',
        ];

        $this->postJson('/api/incoming-emails', $payload)->assertCreated();

        $this->postJson('/api/incoming-emails', $payload)
            ->assertConflict()
            ->assertJsonPath('message', 'This email has already been ingested.');
    }

    public function test_it_marks_vague_emails_with_missing_information(): void
    {
        $response = $this->postJson('/api/incoming-emails', [
            'sender' => 'client@example.com',
            'subject' => 'Something is wrong',
            'body' => 'Bug. Please help soon.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.task_type', 'bug_report')
            ->assertJsonPath('data.missing_information.0', 'More detail about the request or problem.');
    }

    public function test_it_handles_ai_failures_and_records_them(): void
    {
        $response = $this->postJson('/api/incoming-emails', [
            'sender' => 'client@example.com',
            'subject' => 'Processing issue',
            'body' => 'Please simulate-ai-failure for this email so we can test resilience in the pipeline.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Email ingestion failed during AI evaluation.');

        $email = IncomingEmail::first();

        $this->assertNotNull($email);
        $this->assertSame('failed', $email->processing_status);
        $this->assertDatabaseHas('ai_evaluations', [
            'incoming_email_id' => $email->id,
            'status' => 'failed',
        ]);
    }

    public function test_it_approves_a_task_draft_once(): void
    {
        $taskDraft = $this->createTaskDraft();

        $this->postJson("/api/task-drafts/{$taskDraft->id}/approve", [
            'note' => 'Looks valid.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/task-drafts/{$taskDraft->id}/approve", [
            'note' => 'Second pass.',
        ])->assertConflict()
            ->assertJsonPath('message', 'This task draft has already been approved.');

        $this->assertDatabaseHas('approval_decisions', [
            'task_draft_id' => $taskDraft->id,
            'action' => 'approved',
        ]);
    }

    public function test_override_requires_a_reason(): void
    {
        $taskDraft = $this->createTaskDraft();

        $this->postJson("/api/task-drafts/{$taskDraft->id}/override", [
            'title' => 'Manually updated title',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_it_allows_manual_override_and_logs_the_change(): void
    {
        $taskDraft = $this->createTaskDraft();

        $response = $this->postJson("/api/task-drafts/{$taskDraft->id}/override", [
            'reason' => 'The PM clarified the scope on a call.',
            'title' => 'Evaluate partner CSV import request',
            'priority' => 'high',
            'suggested_team' => 'product',
            'missing_information' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'overridden')
            ->assertJsonPath('data.title', 'Evaluate partner CSV import request')
            ->assertJsonPath('data.operator_note', 'The PM clarified the scope on a call.');

        $decision = ApprovalDecision::first();

        $this->assertSame('overridden', $decision->action);
        $this->assertSame('high', $decision->overrides['priority']);
        $this->assertDatabaseHas('audit_logs', [
            'task_draft_id' => $taskDraft->id,
            'action' => 'task_draft.overridden',
        ]);
    }

    public function test_it_returns_a_full_task_draft_view(): void
    {
        $taskDraft = $this->createTaskDraft();

        $this->getJson("/api/task-drafts/{$taskDraft->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $taskDraft->id)
            ->assertJsonPath('data.incoming_email.id', $taskDraft->incoming_email_id);
    }

    private function createTaskDraft(): TaskDraft
    {
        $this->postJson('/api/incoming-emails', [
            'sender' => 'client@example.com',
            'subject' => 'Bulk import feature request',
            'body' => 'We need a CSV import feature because onboarding enterprise partners currently takes too much manual effort.',
        ])->assertCreated();

        return TaskDraft::query()->firstOrFail();
    }
}
