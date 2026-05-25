# ZETA Email Assistant

Small Laravel API that ingests raw emails, interprets them through an AI abstraction layer, and creates reviewable task drafts. The system is intentionally synchronous and simple: every incoming email is stored first, interpreted second, and only becomes actionable after a human reviews the generated draft.

## API

Base URL:

```text
http://127.0.0.1:8000/api
```

Main endpoints:

- `POST /api/incoming-emails`
- `GET /api/task-drafts/{id}`
- `POST /api/task-drafts/{id}/approve`
- `POST /api/task-drafts/{id}/reject`
- `POST /api/task-drafts/{id}/override`

### Example 1: ingest a bug report email

```bash
curl -X POST http://127.0.0.1:8000/api/incoming-emails \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sender": "client@example.com",
    "subject": "Checkout error on production",
    "body": "Customers report a broken checkout flow after clicking Pay. Steps to reproduce: add product to cart, open checkout, confirm payment. The issue happens on app version 3.4.2."
  }'
```

Typical response:

```json
{
  "data": {
    "id": 1,
    "status": "pending_review",
    "task_type": "bug_report",
    "title": "Investigate Checkout error on production",
    "priority": "high",
    "suggested_team": "engineering",
    "confidence_score": 0.88,
    "missing_information": [],
    "suggested_next_action": "Confirm reproduction steps and create an engineering ticket after approval.",
    "incoming_email": {
      "id": 1,
      "sender": "client@example.com",
      "subject": "Checkout error on production",
      "processing_status": "processed"
    }
  }
}
```

This request creates:

- one `incoming_emails` record
- one `task_drafts` record
- one `ai_evaluations` record
- audit log entries for ingestion and draft generation

### Example 2: fetch the generated task draft

```bash
curl http://127.0.0.1:8000/api/task-drafts/1 \
  -H "Accept: application/json"
```

This returns the full review object, including:

- draft fields such as title, summary, priority, confidence
- the original incoming email
- AI evaluation history
- approval decisions
- audit logs

### Example 3: approve a draft

```bash
curl -X POST http://127.0.0.1:8000/api/task-drafts/1/approve \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "note": "The report is clear and includes reproduction steps."
  }'
```

Result:

- draft `status` becomes `approved`
- a new `approval_decisions` record is created
- an audit log entry is created

### Example 4: reject a draft

```bash
curl -X POST http://127.0.0.1:8000/api/task-drafts/2/reject \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "note": "The email is too vague to act on."
  }'
```

Result:

- draft `status` becomes `rejected`
- the operator note is saved
- the rejection is recorded for traceability

### Example 5: override AI-generated fields

```bash
curl -X POST http://127.0.0.1:8000/api/task-drafts/3/override \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reason": "The PM clarified the scope during a call.",
    "title": "Evaluate partner CSV import feature",
    "priority": "high",
    "suggested_team": "product",
    "missing_information": []
  }'
```

Result:

- draft `status` becomes `overridden`
- updated fields are saved on the draft
- the override payload is stored in `approval_decisions`
- an audit log entry records the manual intervention

### Example 6: duplicate email protection

Sending the exact same email twice returns:

```json
{
  "message": "This email has already been ingested."
}
```

with HTTP `409 Conflict`.

### Example 7: AI failure path

If the email body contains `simulate-ai-failure`, the mock interpreter throws a controlled failure:

```bash
curl -X POST http://127.0.0.1:8000/api/incoming-emails \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sender": "client@example.com",
    "subject": "Processing issue",
    "body": "Please simulate-ai-failure for this email so we can test resilience."
  }'
```

Response:

```json
{
  "message": "Email ingestion failed during AI evaluation.",
  "error": "The AI provider was unable to process this email."
}
```

with HTTP `422 Unprocessable Entity`.

The raw email is still saved and the failed AI evaluation is recorded for traceability.

### Example 8: vague email handling

Very short or unclear emails are still accepted, but the draft will usually have:

- lower confidence score
- `missing_information` hints
- a next action that suggests follow-up before execution

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
