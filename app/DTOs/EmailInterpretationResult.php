<?php

namespace App\DTOs;

class EmailInterpretationResult
{
    public function __construct(
        public readonly string $taskType,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $priority,
        public readonly ?string $suggestedTeam,
        public readonly float $confidenceScore,
        public readonly array $missingInformation,
        public readonly string $suggestedNextAction,
        public readonly array $rawOutput,
        public readonly string $provider = 'mock-ai',
        public readonly string $model = 'rules-v1',
    ) {}

    public function toTaskDraftAttributes(): array
    {
        return [
            'task_type' => $this->taskType,
            'title' => $this->title,
            'summary' => $this->summary,
            'priority' => $this->priority,
            'suggested_team' => $this->suggestedTeam,
            'confidence_score' => $this->confidenceScore,
            'missing_information' => $this->missingInformation,
            'suggested_next_action' => $this->suggestedNextAction,
        ];
    }
}
