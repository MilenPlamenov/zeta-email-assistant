<?php

namespace App\Http\Controllers;

use App\Exceptions\AiProcessingException;
use App\Exceptions\DuplicateEmailException;
use App\Http\Requests\StoreIncomingEmailRequest;
use App\Http\Resources\TaskDraftResource;
use App\Services\IncomingEmailProcessor;
use Symfony\Component\HttpFoundation\Response;

class IncomingEmailController extends Controller
{
    public function __construct(
        private readonly IncomingEmailProcessor $processor,
    ) {}

    public function store(StoreIncomingEmailRequest $request)
    {
        try {
            $taskDraft = $this->processor->process(...$request->safe()->only(['sender', 'subject', 'body']));

            return (new TaskDraftResource($taskDraft))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (DuplicateEmailException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (AiProcessingException $exception) {
            return response()->json([
                'message' => 'Email ingestion failed during AI evaluation.',
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
