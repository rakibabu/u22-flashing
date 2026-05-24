<?php

namespace App\Http\Controllers;

use App\Models\TeamDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeamDocumentPdfController extends Controller
{
    public function __invoke(TeamDocument $teamDocument): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isCoach() || auth()->user()?->isPlayer(), 403);
        abort_unless($teamDocument->pdf_path, 404);
        abort_unless(Storage::disk('local')->exists($teamDocument->pdf_path), 404);

        $filename = $teamDocument->original_filename ?: $teamDocument->type.'.pdf';

        return response()->file(
            Storage::disk('local')->path($teamDocument->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $filename).'"',
            ],
        );
    }
}
