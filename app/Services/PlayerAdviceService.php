<?php

namespace App\Services;

use App\Models\CoachNote;
use App\Models\Player;
use App\Models\WeeklyCheckin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlayerAdviceService
{
    /**
     * @return array{status:string, readiness:string, reason:string, advice:string, next_action:string, compliance:int, weight_trend:?float}
     */
    public function evaluate(Player $player, ?WeeklyCheckin $checkin = null): array
    {
        $player->loadMissing(['settings', 'checkins' => fn ($query) => $query->orderByDesc('week_start_date')->limit(4)]);

        $checkin ??= $player->checkins->first();
        $settings = $player->settings;

        if (! $checkin) {
            return $this->result(
                player: $player,
                status: $player->created_at?->lt(now()->subWeeks(2)) ? 'red' : 'orange',
                reason: $player->created_at?->lt(now()->subWeeks(2)) ? '2 weken geen check-in' : 'Geen check-in deze week',
                advice: 'Vul de weekcheck in zodat we gericht kunnen bijsturen.',
                nextAction: 'Stuur een korte reminder om de weekcheck vandaag in te vullen.',
                compliance: 0,
            );
        }

        $compliance = $this->trainingCompliance($player, $checkin);
        $weightTrend = $this->weeklyWeightTrend($player->checkins);
        $twoWeekWeightTrend = $this->twoWeekWeightTrend($player->checkins);

        if ($checkin->week_start_date->lt(now()->startOfWeek()->subWeek())) {
            return $this->result($player, 'red', '2 weken geen check-in', 'Eerst check-in ophalen voordat je belasting verhoogt.', 'Stuur een directe reminder en check kort waarom de check-ins ontbreken.', $compliance, $weightTrend);
        }

        if ($checkin->week_start_date->lt(now()->startOfWeek())) {
            return $this->result($player, 'orange', 'Check-in mist deze week', 'Vul de weekcheck in zodat we gericht kunnen bijsturen.', 'Stuur een reminder om de weekcheck vandaag in te vullen.', $compliance, $weightTrend);
        }

        if ($checkin->pain) {
            return $this->result(
                player: $player,
                status: 'red',
                reason: 'Pijn gemeld'.($checkin->pain_location ? ': '.$checkin->pain_location : ''),
                advice: 'Niet doortrainen alsof er niets is. Check vooral hamstring, kuit, lies, knie of enkel, pas de belasting aan en schakel eventueel fysio in.',
                nextAction: 'Pijn gemeld: check blessurelocatie en pas belasting aan.',
                compliance: $compliance,
                weightTrend: $weightTrend,
            );
        }

        if (($checkin->energy_score ?? 10) <= 4 && ($checkin->soreness_score ?? 0) >= 8) {
            return $this->result($player, 'red', 'Lage energie en hoge vermoeidheid', 'Belasting omlaag. Vervang een intensieve sessie door zone 2/herstel en slaap bij.', 'Schrap deze week een intensieve prikkel en plan herstel/zone 2.', $compliance, $weightTrend);
        }

        if ($player->isMuscleGain()) {
            if ($twoWeekWeightTrend !== null && $twoWeekWeightTrend <= 0) {
                return $this->result($player, 'red', '2 weken geen gewichtstoename', $this->muscleGainAdvice($checkin, true), 'Voeg dagelijks +250 kcal toe en check 20:00 kcal/eiwit.', $compliance, $weightTrend);
            }

            if ($weightTrend !== null && $weightTrend < 0.3) {
                return $this->result($player, 'orange', 'Gewichtstoename lager dan 0.3 kg/week', $this->muscleGainAdvice($checkin), 'Check of dit 2 weken speelt; zo ja dagelijks +250 kcal.', $compliance, $weightTrend);
            }

            if (in_array($checkin->protein_status, ['partial', 'no'], true)) {
                return $this->result(
                    $player,
                    'orange',
                    'Eiwitdoel niet volledig gehaald',
                    'Eerst eiwit fixen: mik op 120-130g per dag. '.$this->proteinDetailSummary($checkin),
                    'Plan per dag 120-130g eiwit voordat je extra kcal finetunet.',
                    $compliance,
                    $weightTrend,
                );
            }
        }

        if ($settings && $checkin->strength_sessions < $settings->strength_target_per_week) {
            return $this->result(
                player: $player,
                status: 'orange',
                reason: 'Te weinig krachttraining',
                advice: $player->isMuscleGain()
                    ? 'Voor spiermassa en belastbaarheid moet je 3 vaste krachtmomenten halen. Plan Gym A, Gym B en Gym C met RPE 7-8.'
                    : 'Kracht onderhouden lukt niet met alleen pickup. Plan Gym A en Gym B of de outdoor alternatieven.',
                nextAction: 'Plan deze week minimaal '.$settings->strength_target_per_week.' krachttrainingen.',
                compliance: $compliance,
                weightTrend: $weightTrend,
            );
        }

        if ($settings && $checkin->conditioning_sessions < $settings->conditioning_target_per_week) {
            return $this->result(
                player: $player,
                status: 'orange',
                reason: 'Te weinig conditie/pickup prikkels',
                advice: match (true) {
                    $player->isConditioning() => 'Je conditieprikkels zijn te laag. Pickup telt mee; mis je pickup, kies C3 HIIT of C4 repeated sprint, niet allebei extra.',
                    $player->isMuscleGain() => 'Maandagpickup is je hoofdprikkel. Als die wegvalt: kies 8x1 min hard/1 min rustig of 10x30 sec court work/30 sec rust.',
                    default => 'Je mist basketbaltempo. Pickup telt mee; voeg een C1 zone 2, C3 HIIT of C5 court block toe passend bij de fase.',
                },
                nextAction: match (true) {
                    $player->isConditioning() => 'Conditievolume te laag: plan repeated sprint of court conditioning.',
                    $player->isMuscleGain() => 'Als maandagpickup niet lukt: plan 1 korte basketbalgerichte conditieprikkel.',
                    default => 'Plan een extra court conditioning of pickup-prikkel.',
                },
                compliance: $compliance,
                weightTrend: $weightTrend,
            );
        }

        if ($settings && $checkin->mobility_sessions < $settings->mobility_target_per_week) {
            return $this->result($player, 'orange', 'Te weinig mobiliteit/preventie', 'Blessurepreventie moet consistenter. Plan 3 blokken van 8 minuten rond trainingen.', 'Plan deze week 3 korte preventieblokken van 8 minuten.', $compliance, $weightTrend);
        }

        if (($checkin->sleep_avg_hours ?? 8) < 7) {
            return $this->result($player, 'orange', 'Slaap onder 7 uur gemiddeld', 'Slaap is nu de makkelijkste winst. Mik deze week op vaste bedtijd en 7+ uur gemiddeld.', 'Zet deze week slaap als hoofdactie: 7+ uur gemiddeld.', $compliance, $weightTrend);
        }

        if ($checkin->had_full_rest_day === false) {
            return $this->result($player, 'orange', 'Geen volledige rustdag', 'Minimaal 1 volledige rustdag per week is onderdeel van het zomerprogramma. Niet alles stapelen; herstel beschermt de volgende intensieve prikkel.', 'Plan deze week minimaal 1 volledige rustdag.', $compliance, $weightTrend);
        }

        $highestRpe = $checkin->highest_session_rpe ?? $checkin->rpe_highest;

        if (($highestRpe ?? 0) >= 9 && ($checkin->energy_score ?? 10) <= 5) {
            return $this->result($player, 'orange', 'RPE hoog met lage energie', 'RPE 9 is alleen kort en bewust. Hou intensiteit scherp maar volume beperkt en voeg herstel of zone 2 toe in plaats van extra HIIT.', 'Vervang een zware sessie door herstel of zone 2.', $compliance, $weightTrend);
        }

        $greenAdvice = $player->isMuscleGain()
            ? 'Goed bezig. Houd 3x kracht, maandagpickup, 3000+ kcal, 120-130g eiwit en je weekgemiddelde gewicht vast.'
            : 'Goed bezig. Houd 2x kracht, 2x conditie/pickup en 3x preventie vast en blijf pijn of vermoeidheid eerlijk melden.';

        return $this->result($player, 'green', 'Op schema', $greenAdvice, 'Geen grote bijsturing nodig; ritme vasthouden.', $compliance, $weightTrend);
    }

    public function markdownFor(Player $player): string
    {
        $evaluation = $this->evaluate($player);
        $latest = $player->checkins()->latest('week_start_date')->first();
        $latestAdvice = $player->coachNotes()->latest()->first();

        return sprintf(
            "- %s\n  - Programma: %s\n  - Program targets: %s\n  - Readiness: %s (%s)\n  - Check-in status: %s\n  - Compliance: %d%%\n  - Gewichtstrend: %s kg/week\n  - Pijn: %s\n  - Energie/slaap: %s / %s uur\n  - Volledige rustdag: %s\n  - Training load: %s\n  - Bulk voeding: %s\n  - Next action: %s\n  - Coachadvies: %s",
            $player->name,
            $player->programName(),
            $this->programTargetSummary($player),
            $evaluation['readiness'],
            $evaluation['reason'],
            $latest?->week_start_date?->isSameDay(now()->startOfWeek()) ? 'ingevuld deze week' : 'mist deze week',
            $evaluation['compliance'],
            $evaluation['weight_trend'] === null ? 'n.v.t.' : number_format($evaluation['weight_trend'], 1),
            $latest?->pain ? 'ja'.($latest->pain_location ? ' - '.$latest->pain_location : '') : 'nee',
            $latest?->energy_score ?? 'n.v.t.',
            $latest?->sleep_avg_hours ?? 'n.v.t.',
            $latest?->had_full_rest_day === null ? 'n.v.t.' : ($latest->had_full_rest_day ? 'ja' : 'nee'),
            $latest?->calculated_training_load ?? 'n.v.t.',
            $this->bulkNutritionSummary($player, $latest),
            $evaluation['next_action'],
            $latestAdvice?->body ?? $evaluation['advice'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function bulkSummary(Player $player): array
    {
        $player->loadMissing(['checkins' => fn ($query) => $query->latest('week_start_date')->limit(4)]);
        $latest = $player->checkins->first();
        $evaluation = $this->evaluate($player, $latest);

        return [
            'current_weight' => $latest?->weight_kg,
            'weight_trend' => $evaluation['weight_trend'],
            'kcal_avg' => $latest?->kcal_avg,
            'protein_status' => $this->proteinStatusLabel($latest?->protein_status),
            'protein_avg_grams' => $latest?->protein_avg_grams,
            'protein_target_days' => $latest?->protein_target_days,
            'protein_notes' => $latest?->protein_notes,
            'strength_sessions' => $latest?->strength_sessions,
            'pickup_monday' => $latest?->pickup_monday,
            'appetite_score' => $latest?->appetite_score,
            'kcal_advice' => $evaluation['advice'],
            'target_weight' => $player->target_weight_kg,
            'stretch_target' => '68-70 kg',
            'season_target' => '70-72 kg',
            'long_term_target' => $player->long_term_target_weight_kg,
            'kcal_minimum' => $player->settings?->kcal_minimum,
            'kcal_training_day' => $player->settings?->kcal_training_day,
            'kcal_pickup_day' => $player->settings?->kcal_pickup_day,
            'protein_target' => $player->settings?->protein_target_min.'-'.$player->settings?->protein_target_max.'g',
        ];
    }

    /**
     * @return Collection<int, array{date:Carbon|string, type:string, title:string, body:string, tone:string}>
     */
    public function timelineFor(Player $player): Collection
    {
        $player->loadMissing(['checkins', 'coachNotes', 'testResults', 'settings']);

        $checkins = $player->checkins->map(fn (WeeklyCheckin $checkin): array => [
            'date' => $checkin->week_start_date,
            'type' => 'Check-in',
            'title' => $checkin->pain ? 'Check-in met pijnmelding' : 'Weekcheck ingevuld',
            'body' => "{$checkin->strength_sessions} kracht, {$checkin->conditioning_sessions} conditie, {$checkin->mobility_sessions} preventie. Rustdag ".($checkin->had_full_rest_day === null ? 'n.v.t.' : ($checkin->had_full_rest_day ? 'ja' : 'nee')).'. Energie '.($checkin->energy_score ?? 'n.v.t.').', slaap '.($checkin->sleep_avg_hours ?? 'n.v.t.').'. '.$this->proteinDetailSummary($checkin),
            'tone' => $checkin->pain ? 'red' : 'neutral',
        ]);

        $pain = $player->checkins
            ->filter(fn (WeeklyCheckin $checkin): bool => $checkin->pain)
            ->map(fn (WeeklyCheckin $checkin): array => [
                'date' => $checkin->week_start_date,
                'type' => 'Blessure',
                'title' => 'Pijnmelding',
                'body' => trim(($checkin->pain_location ?: 'Onbekende locatie').'. '.($checkin->pain_notes ?: 'Geen extra toelichting.')),
                'tone' => 'red',
            ]);

        $notes = $player->coachNotes->map(fn (CoachNote $note): array => [
            'date' => $note->created_at,
            'type' => 'Coachadvies',
            'title' => $note->title,
            'body' => $note->body,
            'tone' => $note->visible_to_player ? 'green' : 'orange',
        ]);

        $tests = $player->testResults->map(fn ($test): array => [
            'date' => $test->test_date,
            'type' => 'Testresultaat',
            'title' => 'Testmoment',
            'body' => '20m: '.($test->sprint_20m_seconds ?? 'n.v.t.').' sec, 5-min: '.($test->five_min_run_meters ?? 'n.v.t.').' m, gewicht: '.($test->body_weight_kg ?? 'n.v.t.').'.',
            'tone' => 'neutral',
        ]);

        $program = collect([[
            'date' => $player->updated_at,
            'type' => 'Programma',
            'title' => 'Programma-instelling',
            'body' => $player->programName().' met '.$player->settings?->strength_target_per_week.'x kracht, '.$player->settings?->conditioning_target_per_week.'x conditie en '.$player->settings?->mobility_target_per_week.'x mobiliteit per week.',
            'tone' => 'neutral',
        ]]);

        return $checkins
            ->concat($pain)
            ->concat($notes)
            ->concat($tests)
            ->concat($program)
            ->sortByDesc('date')
            ->values();
    }

    private function muscleGainAdvice(WeeklyCheckin $checkin, bool $urgent = false): string
    {
        if (($checkin->kcal_avg ?? 0) < 3300) {
            return 'Je eet nog te weinig voor spiermassa. Deze week elke dag +250 kcal toevoegen. Kies bijvoorbeeld een smoothie, extra kwark, pindakaas, noten, olijfolie of een extra boterham.';
        }

        return ($urgent ? 'Je valt af of blijft gelijk over 2 weken. ' : '').'Je lijkt meer nodig te hebben dan gepland. Verhoog je dagtarget met +250 kcal.';
    }

    private function proteinDetailSummary(?WeeklyCheckin $checkin): string
    {
        if (! $checkin || ! in_array($checkin->protein_status, ['partial', 'no'], true)) {
            return '';
        }

        $parts = [
            'Status: '.$this->proteinStatusLabel($checkin->protein_status),
        ];

        if ($checkin->protein_avg_grams !== null) {
            $parts[] = 'gemiddeld '.$checkin->protein_avg_grams.'g/dag';
        }

        if ($checkin->protein_target_days !== null) {
            $parts[] = $checkin->protein_target_days.'/7 dagen doel gehaald';
        }

        if ($checkin->protein_notes) {
            $parts[] = 'toelichting: '.$checkin->protein_notes;
        }

        return implode(', ', $parts).'.';
    }

    private function bulkNutritionSummary(Player $player, ?WeeklyCheckin $checkin): string
    {
        if (! $player->isMuscleGain()) {
            return 'n.v.t.';
        }

        if (! $checkin) {
            return 'geen check-in';
        }

        $proteinDetails = $this->proteinDetailSummary($checkin);

        return 'kcal '.($checkin->kcal_avg ?? 'n.v.t.').', eiwit '.$this->proteinStatusLabel($checkin->protein_status).($proteinDetails ? ' ('.$proteinDetails.')' : '');
    }

    private function proteinStatusLabel(?string $status): string
    {
        return match ($status) {
            'yes' => 'Ja (6-7 dagen)',
            'partial' => 'Soms (3-5 dagen)',
            'no' => 'Nee (0-2 dagen)',
            default => 'n.v.t.',
        };
    }

    private function trainingCompliance(Player $player, WeeklyCheckin $checkin): int
    {
        $settings = $player->settings;

        if (! $settings) {
            return 0;
        }

        $target = max(1, $settings->strength_target_per_week + $settings->conditioning_target_per_week + $settings->mobility_target_per_week);
        $done = min($settings->strength_target_per_week, $checkin->strength_sessions)
            + min($settings->conditioning_target_per_week, $checkin->conditioning_sessions)
            + min($settings->mobility_target_per_week, $checkin->mobility_sessions);

        return (int) round(($done / $target) * 100);
    }

    /**
     * @param  Collection<int, WeeklyCheckin>  $checkins
     */
    private function weeklyWeightTrend(Collection $checkins): ?float
    {
        $withWeight = $checkins->filter(fn (WeeklyCheckin $checkin): bool => $checkin->weight_kg !== null)->values();

        if ($withWeight->count() < 2) {
            return null;
        }

        return (float) $withWeight->first()->weight_kg - (float) $withWeight->get(1)->weight_kg;
    }

    /**
     * @param  Collection<int, WeeklyCheckin>  $checkins
     */
    private function twoWeekWeightTrend(Collection $checkins): ?float
    {
        $withWeight = $checkins->filter(fn (WeeklyCheckin $checkin): bool => $checkin->weight_kg !== null)->values();

        if ($withWeight->count() < 3) {
            return null;
        }

        return (float) $withWeight->first()->weight_kg - (float) $withWeight->get(2)->weight_kg;
    }

    private function programTargetSummary(Player $player): string
    {
        if ($player->isMuscleGain()) {
            return '3x kracht, maandagpickup, geen donderdagpickup, 3000 kcal minimum, gymdag 3300-3400 kcal, pickupdag 3600 kcal, 120-130g eiwit, 66-68 kg op 17 augustus is goed en 68-70 kg stretch.';
        }

        return '2x kracht, 2x conditie/pickup, 3x 8 minuten preventie en minimaal 1 volledige rustdag.';
    }

    /**
     * @return array{status:string, readiness:string, reason:string, advice:string, next_action:string, compliance:int, weight_trend:?float}
     */
    private function result(Player $player, string $status, string $reason, string $advice, string $nextAction, int $compliance, ?float $weightTrend = null): array
    {
        return [
            'status' => $status,
            'readiness' => match ($status) {
                'red' => 'direct aandacht nodig',
                'orange' => 'bijsturen',
                default => 'op schema',
            },
            'reason' => $reason,
            'advice' => $advice,
            'next_action' => $nextAction,
            'compliance' => $compliance,
            'weight_trend' => $weightTrend,
        ];
    }
}
