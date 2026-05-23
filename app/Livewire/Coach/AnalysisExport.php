<?php

namespace App\Livewire\Coach;

use App\Models\Player;
use App\Services\PlayerAdviceService;
use Livewire\Component;

class AnalysisExport extends Component
{
    public function render(PlayerAdviceService $adviceService)
    {
        $players = Player::query()
            ->with(['settings', 'coachNotes' => fn ($query) => $query->latest(), 'checkins' => fn ($query) => $query->latest('week_start_date')->limit(3)])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $markdown = "Analyseer deze U22 zomerprogramma data en geef concrete bijstuuradviezen per speler. Houd rekening met het spelersprogramma van 11 mei t/m 16 augustus 2026: onderhoud/conditie meestal 2x kracht, 2x conditie/pickup, 3x 8 minuten preventie en minimaal 1 rustdag. Muscle-gain spelers hebben 3x kracht, maandagpickup, geen donderdagpickup, 3000 kcal minimum, 3300-3400 kcal op gymdagen, 3600 kcal op pickupdag en 120-130g eiwit.\n\n";
        $markdown .= "# Teamdoel\nFlashing Heiloo U22 komt op maandag 17 augustus fit en fris binnen, zodat de trainingen direct naar tactiek, teamafspraken en wedstrijdtempo kunnen. Monitor readiness, training, pijn, herstel en voeding. Voor spiermassa-spelers is 66-68 kg richting 17 augustus goed en 68-70 kg stretch, zolang snelheid, sprong, eerste stap en belastbaarheid goed blijven.\n\n";
        $markdown .= '# Week '.now()->startOfWeek()->toDateString()."\n\n";
        $markdown .= $players->map(fn (Player $player): string => $adviceService->markdownFor($player))->implode("\n");

        return view('livewire.coach.analysis-export', ['markdown' => $markdown])->layout('layouts.app');
    }
}
