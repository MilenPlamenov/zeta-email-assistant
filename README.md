# ZETA Email Assistant

Small Laravel API that ingests raw emails, interprets them through an AI abstraction layer, and creates reviewable task drafts. The system is intentionally synchronous and simple: every incoming email is stored first, interpreted second, and only becomes actionable after a human reviews the generated draft.

## API

`POST /api/incoming-emails`

Accepts:

```json
{
  "sender": "client@example.com",
  "subject": "Checkout error on production",
  "body": "Customers report a broken checkout flow with clear reproduction steps..."
}
```

Creates:

- `incoming_emails` record with the raw message
- `ai_evaluations` record for the interpretation attempt
- `task_drafts` record in `pending_review`
- `audit_logs` entries for traceability

Other endpoints:

- `GET /api/task-drafts/{id}`
- `POST /api/task-drafts/{id}/approve`
- `POST /api/task-drafts/{id}/reject`
- `POST /api/task-drafts/{id}/override`

## Architecture

The project follows a thin-controller, service-oriented structure:

- Controllers only coordinate HTTP input/output.
- `IncomingEmailProcessor` owns ingestion, duplicate detection, AI interpretation, persistence, and failure handling.
- `TaskDraftReviewService` owns review-state transitions: approve, reject, override.
- `AuditLogger` centralizes audit entries instead of scattering them across controllers.
- `EmailTaskInterpreter` is an interface, so the mocked interpreter can later be replaced with OpenAI, Claude, or another provider without changing business flow.

### AI abstraction

Current implementation uses `MockEmailTaskInterpreter`, a deterministic rules-based adapter that classifies emails into:

- `bug_report`
- `feature_request`
- `customer_feedback`

It also returns:

- title
- summary
- priority
- suggested team
- confidence score
- missing information
- suggested next action

The mock intentionally simulates one failure mode when the email contains `simulate-ai-failure`, which makes it easy to test the unhappy path.

## Data model

### `incoming_emails`

Stores raw sender, subject, body, processing status, failure reason, and a unique content hash for duplicate protection.

### `task_drafts`

Stores the structured suggestion to be reviewed by a human:

- task type
- title
- summary
- priority
- suggested team
- confidence score
- missing information
- suggested next action
- review status and operator note

### `ai_evaluations`

Stores provider/model metadata, success or failure status, confidence, raw output, and error details if interpretation fails.

### `approval_decisions`

Stores explicit human actions:

- approved
- rejected
- overridden

It also stores the operator note and override payload when applicable.

### `audit_logs`

Captures workflow events such as:

- email received
- task draft generated
- AI evaluation failed
- task approved
- task rejected
- task overridden

## Human approval flow

1. An email is submitted to `POST /api/incoming-emails`.
2. The backend stores the raw email and runs the interpreter.
3. A `task_draft` is created with `pending_review`.
4. A human operator can:
   - approve the draft
   - reject the draft
   - override one or more AI-generated fields with a required reason
5. Every action is persisted as both a decision record and an audit log.

## Failure handling included

Implemented failure and edge-case handling:

- missing required request fields
- duplicate email ingestion
- AI evaluation failure
- too-vague email content that lowers confidence and marks missing information
- approving the same draft twice
- override attempt without a reason
- review actions blocked after terminal states where appropriate

## Trade-offs and simplifications

- No authentication layer, because the assignment focuses on backend architecture and workflow.
- No queues, because synchronous processing keeps the example smaller and easier to review.
- No real LLM integration yet, because the interface boundary is the important part for this stage.
- No final `tasks` table, because the brief asks for reviewable drafts rather than automatic task creation.

## What I would improve next

- Replace the mock interpreter with a real provider adapter and prompt versioning.
- Move ingestion and AI evaluation to queued jobs with retries.
- Add authentication/authorization for review actions.
- Add idempotency keys or mailbox message IDs for stronger duplicate detection.
- Introduce a final approved task entity or downstream integration with Jira/ClickUp.
- Add richer audit actor context once authentication exists.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The current `.env` is configured for MySQL. Update credentials as needed before running migrations.

## Testing

Feature tests cover ingestion, duplicates, AI failure, vague emails, approval rules, overrides, and fetch flow.

```bash
php artisan test
```

In this execution environment the test run could not complete because `pdo_sqlite` is not installed and the configured MySQL server was not reachable, but the route table and PHP syntax were validated successfully.
