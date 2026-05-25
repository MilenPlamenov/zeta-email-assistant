<?php

use App\Http\Controllers\IncomingEmailController;
use App\Http\Controllers\TaskDraftController;
use App\Http\Controllers\TaskDraftReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/incoming-emails', [IncomingEmailController::class, 'store']);
Route::get('/task-drafts/{taskDraft}', [TaskDraftController::class, 'show']);
Route::post('/task-drafts/{taskDraft}/approve', [TaskDraftReviewController::class, 'approve']);
Route::post('/task-drafts/{taskDraft}/reject', [TaskDraftReviewController::class, 'reject']);
Route::post('/task-drafts/{taskDraft}/override', [TaskDraftReviewController::class, 'override']);
