<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\ProgramTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerProgramPdfController extends Controller
{
    public function player(): BinaryFileResponse
    {
        $player = auth()->user()->player;
        abort_unless($player instanceof Player, 403);

        $template = ProgramTemplate::query()
            ->where('type', $player->program_type)
            ->firstOrFail();

        return $this->show($template);
    }

    public function coach(ProgramTemplate $programTemplate): BinaryFileResponse
    {
        return $this->show($programTemplate);
    }

    public function store(Request $request, ProgramTemplate $programTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'training_program_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($programTemplate->training_program_pdf_path) {
            Storage::disk('local')->delete($programTemplate->training_program_pdf_path);
        }

        $filename = Str::slug($programTemplate->type).'-programma-'.now()->format('YmdHis').'.pdf';

        $programTemplate->update([
            'training_program_pdf_path' => $validated['training_program_pdf']->storeAs(
                "program-templates/{$programTemplate->type}",
                $filename,
                'local',
            ),
        ]);

        return back()->with('saved_program_template_id', $programTemplate->id);
    }

    private function show(ProgramTemplate $template): BinaryFileResponse
    {
        abort_unless($template->training_program_pdf_path, 404);
        abort_unless(Storage::disk('local')->exists($template->training_program_pdf_path), 404);

        return response()->file(
            Storage::disk('local')->path($template->training_program_pdf_path),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
