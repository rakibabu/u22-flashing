<?php

namespace App\Http\Controllers\Coach;

use App\Actions\ExerciseLibrary\ExportExerciseLibrary;
use App\Actions\ExerciseLibrary\ImportExerciseLibrary;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportExerciseLibraryRequest;
use App\Models\ExerciseLibraryItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExerciseLibraryTransferController extends Controller
{
    public function export(ExportExerciseLibrary $exportExerciseLibrary): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('viewAny', ExerciseLibraryItem::class), 403);

        $path = $exportExerciseLibrary->handle();

        return response()->download($path, 'oefeningen-'.now()->format('Y-m-d').'.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    public function import(ImportExerciseLibraryRequest $request, ImportExerciseLibrary $importExerciseLibrary)
    {
        $count = $importExerciseLibrary->handle($request->file('archive'), $request->user());

        return to_route('coach.exercises.index')->with('exercise-imported', trans_choice('{1} 1 oefening geïmporteerd.|[2,*] :count oefeningen geïmporteerd.', $count));
    }
}
