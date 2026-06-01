<?php

namespace App\Livewire\Coach;

use App\Models\Player;
use App\Services\PlayerAdviceService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnalysisExport extends Component
{
    public string $week = '';

    public function mount(): void
    {
        $this->week = $this->formatWeek(now()->startOfWeek()->subWeek());
    }

    public function previousWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart()->subWeek());
    }

    public function nextWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart()->addWeek());
    }

    public function currentWeek(): void
    {
        $this->week = $this->formatWeek(now()->startOfWeek());
    }

    public function updatedWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart());
    }

    public function render(PlayerAdviceService $adviceService): View
    {
        $weekStart = $this->selectedWeekStart();
        $players = $adviceService->playersForAnalysis($weekStart);

        $markdown = "Schrijf persoonlijke coachadviezen voor de onderstaande spelers. Neem alleen deze spelers mee: zij hebben de weekcheck voor de gekozen week ingediend. Gebruik de gekozen week als basis en gebruik eerdere ingediende check-ins alleen als context en trend. Schrijf geen generieke adviezen of placeholders; maak elk advies expliciet aan de speler en zijn data gekoppeld.\n\n";
        $markdown .= "# Teamdoel\nFlashing Heiloo U22 komt op maandag 17 augustus fit en fris binnen, zodat de trainingen direct naar tactiek, teamafspraken en wedstrijdtempo kunnen. Monitor readiness, training, pijn, herstel en voeding.\n\n";
        $markdown .= '# Adviesweek '.$weekStart->toDateString().' (week '.$weekStart->isoWeek().")\n\n";
        $markdown .= $players->isEmpty()
            ? 'Geen spelers met een ingediende check-in voor deze week.'
            : $players->map(fn (Player $player): string => $adviceService->analysisMarkdownFor($player, $weekStart))->filter()->implode("\n\n");

        return view('livewire.coach.analysis-export', [
            'markdown' => $markdown,
            'players' => $players,
            'week' => $this->week,
            'currentWeekValue' => $this->formatWeek(now()->startOfWeek()),
            'isCurrentWeek' => $weekStart->isSameDay(now()->startOfWeek()),
            'selectedWeekNumber' => $weekStart->isoWeek(),
            'selectedWeekRange' => $weekStart->format('d-m-Y').' t/m '.$weekStart->copy()->endOfWeek()->format('d-m-Y'),
        ])->layout('layouts.app');
    }

    private function selectedWeekStart(): CarbonInterface
    {
        if (preg_match('/^(?<year>\d{4})-W(?<week>\d{2})$/', $this->week, $matches) === 1) {
            return $this->clampToCurrentWeek(
                now()->setISODate((int) $matches['year'], (int) $matches['week'])->startOfWeek()
            );
        }

        return now()->startOfWeek()->subWeek();
    }

    private function clampToCurrentWeek(CarbonInterface $weekStart): CarbonInterface
    {
        $currentWeekStart = now()->startOfWeek();

        return $weekStart->gt($currentWeekStart) ? $currentWeekStart : $weekStart;
    }

    private function formatWeek(CarbonInterface $weekStart): string
    {
        return $weekStart->copy()->startOfWeek()->format('o-\WW');
    }
}
