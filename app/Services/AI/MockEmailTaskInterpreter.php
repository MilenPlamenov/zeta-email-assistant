<?php

namespace App\Services\AI;

use App\DTOs\EmailInterpretationResult;
use App\Exceptions\AiProcessingException;
use Illuminate\Support\Str;

class MockEmailTaskInterpreter implements EmailTaskInterpreter
{
    public function interpret(string $sender, string $subject, string $body): EmailInterpretationResult
    {
        $normalized = Str::lower(trim($subject.' '.$body));

        if (Str::contains($normalized, 'simulate-ai-failure')) {
            throw new AiProcessingException('The AI provider was unable to process this email.');
        }

        $taskType = 'customer_feedback';
        $priority = 'medium';
        $suggestedTeam = 'operations';
        $confidence = 0.72;
        $missingInformation = [];
        $nextAction = 'Review the draft and decide whether follow-up with the sender is needed.';

        if (Str::contains($normalized, ['bug', 'error', 'broken', 'issue', 'fail'])) {
            $taskType = 'bug_report';
            $priority = 'high';
            $suggestedTeam = 'engineering';
            $confidence = 0.88;
            $nextAction = 'Confirm reproduction steps and create an engineering ticket after approval.';
        } elseif (Str::contains($normalized, ['feature', 'improvement', 'request', 'enhancement'])) {
            $taskType = 'feature_request';
            $priority = 'medium';
            $suggestedTeam = 'product';
            $confidence = 0.81;
            $nextAction = 'Validate business value and gather scope details before backlog triage.';
        }

        if (str_word_count($body) < 12) {
            $confidence -= 0.35;
            $missingInformation[] = 'More detail about the request or problem.';
            $nextAction = 'Contact the sender for clarification before actioning this draft.';
        }

        if ($taskType === 'bug_report' && ! Str::contains($normalized, ['step', 'reproduce', 'screen', 'version'])) {
            $confidence -= 0.12;
            $missingInformation[] = 'Reproduction steps or environment details.';
        }

        if ($taskType === 'feature_request' && ! Str::contains($normalized, ['because', 'impact', 'benefit', 'need'])) {
            $confidence -= 0.1;
            $missingInformation[] = 'Business context or expected user impact.';
        }

        $confidence = max(0.15, round($confidence, 2));
        $cleanSubject = trim($subject) !== '' ? trim($subject) : 'Email without subject';
        $titlePrefix = match ($taskType) {
            'bug_report' => 'Investigate',
            'feature_request' => 'Evaluate',
            default => 'Review',
        };

        return new EmailInterpretationResult(
            taskType: $taskType,
            title: $titlePrefix.' '.$cleanSubject,
            summary: Str::limit(trim(preg_replace('/\s+/', ' ', $body)), 300, end: '...'),
            priority: $priority,
            suggestedTeam: $suggestedTeam,
            confidenceScore: $confidence,
            missingInformation: array_values(array_unique($missingInformation)),
            suggestedNextAction: $nextAction,
            rawOutput: [
                'sender' => $sender,
                'classification' => $taskType,
                'signals' => [
                    'subject' => $subject,
                    'body_excerpt' => Str::limit($body, 120, end: '...'),
                ],
            ],
        );
    }
}
