# ZETA Case — Laravel + AI: Email-to-Task Assistant

## Overview

This project is a lightweight backend system built with Laravel that transforms incoming email messages into structured task drafts using an AI abstraction layer.

The goal is not to automatically create tasks, but to assist a human operator (Project Manager / Operations Specialist) by generating a suggested task draft that must be reviewed, approved, or modified before becoming actionable.

The system is designed with a clear separation between:
- raw email ingestion
- AI-based interpretation
- human review and decision-making

---

## Goal

The main goal of this system is to:

- Convert unstructured email content into structured task suggestions
- Assist internal teams in organizing bug reports, feature requests, and feedback
- Ensure all AI-generated outputs are reviewed by a human before being accepted
- Provide traceability for decisions, overrides, and AI evaluations

---

## Core Idea

Emails often contain mixed or unstructured information such as:

- bug reports
- feature requests
- customer feedback
- unclear or partial requirements

This system uses an AI layer to interpret the email and produce a structured task draft containing:

- task type
- title
- summary
- priority
- suggested project/team
- confidence score
- missing information
- suggested next action

A human operator then decides whether to:
- approve the draft
- reject it
- override fields manually

---

## Key Features

### 1. Email Ingestion API
Accepts incoming emails via API:

- sender
- subject
- body

Stores raw email data for processing.

---

### 2. AI Processing Layer (Abstracted)

The system uses an AI service abstraction:

- Can be mocked (default implementation)
- Can be replaced with OpenAI / Claude / other providers
- No business logic is hardcoded in controllers

The AI returns a structured task draft suggestion.

---

### 3. Task Draft Generation

Each email results in a `TaskDraft` entity containing:

- structured task data
- AI confidence score
- missing or unclear information
- suggested next actions

---

### 4. Human-in-the-Loop Workflow

A human operator can:

- **Approve** → confirms task draft as valid
- **Reject** → discards the suggestion
- **Override** → modifies AI-generated fields manually

All actions are logged for audit purposes.

---

### 5. Audit & Traceability

The system tracks:

- incoming emails
- AI evaluations
- approval decisions
- overrides and manual edits

This ensures full transparency and traceability of decisions.

---

## Architecture Summary

The system follows a service-oriented architecture:

- Controllers are thin and handle only HTTP requests
- Business logic is moved into services
- AI is accessed via an interface (dependency injection)
- Database models represent each stage of the workflow

---

## Key Design Principles

- AI is replaceable (no vendor lock-in)
- Human validation is mandatory
- Every decision is traceable
- System favors simplicity over overengineering

---

## Limitations / Simplifications

- AI implementation may be mocked
- No authentication layer (can be added later)
- No queue system for async processing
- Minimal UI (API-only system)

---

## Future Improvements

- Integration with real AI providers (OpenAI / Claude)
- Async processing via queues (Redis / Horizon)
- Integration with task management tools (ClickUp, Jira, Trello)
- Web dashboard for task review
- Email ingestion via webhook or IMAP listener

---

## Required output
1. Working Laravel API

POST /api/incoming-emails
GET /api/task-drafts/{id}
POST /api/task-drafts/{id}/approve
POST /api/task-drafts/{id}/reject
POST /api/task-drafts/{id}/override
2. Database model — migrations for incoming emails, task drafts, AI evaluations, approval decisions, audit logs.

3. AI abstraction — a service/interface for turning raw email content into a structured draft. No hardcoded logic in controllers.

4. Human review flow — operator can approve, reject, override fields, and add a note.

5. Failure handling — at least some of: AI fails, missing fields, duplicate email, approved twice, override without reason, email too vague.

6. README.md — start with ZETA. Cover architecture, data model, AI abstraction, human approval flow, trade-offs, simplifications, and what to improve.


## Summary

This system demonstrates how AI can assist in converting unstructured communication into structured work items, while keeping humans in control of final decisions.

The design prioritizes clarity, extensibility, and production readiness without overengineering the initial implementation.