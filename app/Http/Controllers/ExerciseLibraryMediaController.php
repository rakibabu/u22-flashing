<?php

namespace App\Http\Controllers;

use App\Models\ExerciseLibraryItem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExerciseLibraryMediaController extends Controller
{
    public function __invoke(ExerciseLibraryItem $exercise): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('view', $exercise), 403);
        abort_unless($exercise->media_path && Storage::disk('local')->exists($exercise->media_path), 404);

        return response()->file(Storage::disk('local')->path($exercise->media_path), ['Content-Type' => $exercise->media_type ?? 'application/octet-stream']);
    }
}
