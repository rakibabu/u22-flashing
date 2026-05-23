<?php

use App\Http\Controllers\InviteActivationController;
use App\Http\Controllers\PlayerProgramPdfController;
use App\Livewire\Coach\Advice\Index as CoachAdviceIndex;
use App\Livewire\Coach\AnalysisExport;
use App\Livewire\Coach\Checkins\Index as CoachCheckinsIndex;
use App\Livewire\Coach\Checkins\Show as CoachCheckinsShow;
use App\Livewire\Coach\Dashboard as CoachDashboard;
use App\Livewire\Coach\Players\CheckinPreview as CoachPlayersCheckinPreview;
use App\Livewire\Coach\Players\Create as CoachPlayersCreate;
use App\Livewire\Coach\Players\Edit as CoachPlayersEdit;
use App\Livewire\Coach\Players\Index as CoachPlayersIndex;
use App\Livewire\Coach\Players\Show as CoachPlayersShow;
use App\Livewire\Coach\Tests\Index as CoachTestsIndex;
use App\Livewire\Player\Advice as PlayerAdvice;
use App\Livewire\Player\Checkin as PlayerCheckin;
use App\Livewire\Player\Home as PlayerHome;
use App\Livewire\Player\Program as PlayerProgram;
use App\Livewire\Player\Progress as PlayerProgress;
use App\Livewire\Public\TeamActivation;
use App\Models\Player;
use App\Services\PlayerAdviceService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'))->name('home');

Route::get('/invite/{token}', [InviteActivationController::class, 'show'])->name('invite.show');
Route::post('/invite/{token}', [InviteActivationController::class, 'store'])->name('invite.store');
Route::get('/activate/{token}', TeamActivation::class)->middleware('guest')->name('team-invite.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn () => auth()->user()->isCoach()
        ? redirect()->route('coach.dashboard')
        : redirect()->route('player.home'))->name('dashboard');
});

Route::middleware(['auth', 'role:coach'])->prefix('coach')->name('coach.')->group(function () {
    Route::get('dashboard', CoachDashboard::class)->name('dashboard');
    Route::get('players', CoachPlayersIndex::class)->name('players.index');
    Route::get('players/create', CoachPlayersCreate::class)->name('players.create');
    Route::get('players/{player}', CoachPlayersShow::class)->name('players.show');
    Route::get('program-templates/{programTemplate}/pdf', [PlayerProgramPdfController::class, 'coach'])->name('program-templates.pdf');
    Route::post('program-templates/{programTemplate}/pdf', [PlayerProgramPdfController::class, 'store'])->name('program-templates.pdf.store');
    Route::get('players/{player}/edit', CoachPlayersEdit::class)->name('players.edit');
    Route::get('players/{player}/checkin-preview', CoachPlayersCheckinPreview::class)->name('players.checkin-preview');
    Route::get('checkins', CoachCheckinsIndex::class)->name('checkins.index');
    Route::get('checkins/{weeklyCheckin}', CoachCheckinsShow::class)->name('checkins.show');
    Route::get('tests', CoachTestsIndex::class)->name('tests.index');
    Route::get('advice', CoachAdviceIndex::class)->name('advice.index');
    Route::get('analysis-export', AnalysisExport::class)->name('analysis-export');
    Route::get('analysis-export.csv', function (PlayerAdviceService $adviceService) {
        $players = Player::query()->with(['settings', 'latestCheckin', 'checkins' => fn ($query) => $query->latest('week_start_date')->limit(3)])->where('active', true)->orderBy('name')->get();

        return response()->streamDownload(function () use ($players, $adviceService): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['player', 'program', 'readiness', 'status', 'reason', 'compliance', 'week_start', 'weight_kg', 'weight_trend', 'strength', 'conditioning', 'mobility', 'rest_day', 'sleep', 'energy', 'pain', 'training_load', 'kcal_avg', 'protein_status', 'protein_avg_grams', 'protein_target_days', 'protein_notes', 'next_action', 'advice']);

            foreach ($players as $player) {
                $evaluation = $adviceService->evaluate($player);
                $checkin = $player->latestCheckin;

                fputcsv($out, [
                    $player->name,
                    $player->programName(),
                    $evaluation['readiness'],
                    $evaluation['status'],
                    $evaluation['reason'],
                    $evaluation['compliance'],
                    $checkin?->week_start_date?->toDateString(),
                    $checkin?->weight_kg,
                    $evaluation['weight_trend'],
                    $checkin?->strength_sessions,
                    $checkin?->conditioning_sessions,
                    $checkin?->mobility_sessions,
                    $checkin?->had_full_rest_day === null ? null : ($checkin->had_full_rest_day ? 'yes' : 'no'),
                    $checkin?->sleep_avg_hours,
                    $checkin?->energy_score,
                    $checkin?->pain ? 'yes' : 'no',
                    $checkin?->calculated_training_load,
                    $checkin?->kcal_avg,
                    match ($checkin?->protein_status) {
                        'yes' => 'Ja (6-7 dagen)',
                        'partial' => 'Soms (3-5 dagen)',
                        'no' => 'Nee (0-2 dagen)',
                        default => null,
                    },
                    $checkin?->protein_avg_grams,
                    $checkin?->protein_target_days,
                    $checkin?->protein_notes,
                    $evaluation['next_action'],
                    $evaluation['advice'],
                ]);
            }

            fclose($out);
        }, 'u22-analysis-export.csv', ['Content-Type' => 'text/csv']);
    })->name('analysis-export.csv');
});

Route::middleware(['auth', 'role:player'])->prefix('player')->name('player.')->group(function () {
    Route::get('home', PlayerHome::class)->name('home');
    Route::get('program', PlayerProgram::class)->name('program');
    Route::get('program/pdf', [PlayerProgramPdfController::class, 'player'])->name('program.pdf');
    Route::get('checkin', PlayerCheckin::class)->name('checkin');
    Route::get('progress', PlayerProgress::class)->name('progress');
    Route::get('advice', PlayerAdvice::class)->name('advice');
});

require __DIR__.'/settings.php';
