<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerProgramPdfController extends Controller
{
    use AuthorizesRequests;

    public function player(): BinaryFileResponse
    {
        $player = auth()->user()->player;
        abort_unless($player instanceof Player, 403);

        return $this->show($player);
    }

    public function coach(Player $player): BinaryFileResponse
    {
        return $this->show($player);
    }

    private function show(Player $player): BinaryFileResponse
    {
        $this->authorize('view', $player);

        abort_unless($player->training_program_pdf_path, 404);
        abort_unless(Storage::disk('local')->exists($player->training_program_pdf_path), 404);

        return response()->file(
            Storage::disk('local')->path($player->training_program_pdf_path),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
