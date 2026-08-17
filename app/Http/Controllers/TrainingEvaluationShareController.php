<?php

namespace App\Http\Controllers;

use App\Enums\TrainingBlockRunStatus;
use App\Http\Requests\StoreTrainingRunFeedbackRequest;
use App\Models\TrainingRun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class TrainingEvaluationShareController extends Controller
{
    public function show(TrainingRun $trainingRun): View
    {
        $trainingRun->loadMissing(['trainingSession', 'blockRuns.trainingBlock']);
        $completedBlocks = $trainingRun->blockRuns
            ->filter(fn ($blockRun): bool => $blockRun->status === TrainingBlockRunStatus::Completed)
            ->count();
        $skippedBlocks = $trainingRun->blockRuns
            ->filter(fn ($blockRun): bool => $blockRun->status === TrainingBlockRunStatus::Skipped)
            ->count();

        return view('training-review-share', [
            'run' => $trainingRun,
            'completedBlocks' => $completedBlocks,
            'skippedBlocks' => $skippedBlocks,
            'feedbackUrl' => URL::temporarySignedRoute('training-runs.evaluation-feedback', now()->addDays(30), ['trainingRun' => $trainingRun]),
            'shareDescription' => "Bekijk de evaluatie van '{$trainingRun->trainingSession->title}' en deel je feedback met Flashing Heiloo U22.",
        ]);
    }

    public function store(StoreTrainingRunFeedbackRequest $request, TrainingRun $trainingRun): RedirectResponse
    {
        $trainingRun->feedback()->create($request->validated());

        return redirect()->to(
            URL::temporarySignedRoute('training-runs.evaluation-share', now()->addDays(30), ['trainingRun' => $trainingRun]),
        )->with('training-review-feedback-saved', 'Dank je. Je feedback is gedeeld met de coaches.');
    }
}
