<?php

namespace App\Http\Controllers;

use App\Exceptions\TaskDraftStateException;
use App\Http\Requests\OverrideTaskDraftRequest;
use App\Http\Requests\ReviewTaskDraftRequest;
use App\Http\Resources\TaskDraftResource;
use App\Models\TaskDraft;
use App\Services\TaskDraftReviewService;
use Symfony\Component\HttpFoundation\Response;

class TaskDraftReviewController extends Controller
{
    public function __construct(
        private readonly TaskDraftReviewService $reviewService,
    ) {}

    public function approve(ReviewTaskDraftRequest $request, TaskDraft $taskDraft)
    {
        return $this->handleStateChange(fn () => $this->reviewService->approve($taskDraft, $request->validated('note')));
    }

    public function reject(ReviewTaskDraftRequest $request, TaskDraft $taskDraft)
    {
        return $this->handleStateChange(fn () => $this->reviewService->reject($taskDraft, $request->validated('note')));
    }

    public function override(OverrideTaskDraftRequest $request, TaskDraft $taskDraft)
    {
        $payload = $request->safe()->except('reason');

        return $this->handleStateChange(
            fn () => $this->reviewService->override($taskDraft, $payload, $request->validated('reason'))
        );
    }

    private function handleStateChange(callable $callback)
    {
        try {
            return new TaskDraftResource($callback());
        } catch (TaskDraftStateException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }
    }
}
