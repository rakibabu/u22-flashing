<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlayerCheckinSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public WeeklyCheckin $weeklyCheckin, public User $coach)
    {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $this->weeklyCheckin->loadMissing('player');

        return new Envelope(
            subject: $this->weeklyCheckin->player->name.' heeft zijn weekcheck ingevuld',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->weeklyCheckin->loadMissing('player');

        return new Content(
            markdown: 'mail.player-checkin-submitted',
            with: [
                'checkinUrl' => route('coach.checkins.show', $this->weeklyCheckin),
                'playerUrl' => route('coach.players.show', $this->weeklyCheckin->player),
                'summaryRows' => $this->summaryRows(),
                'nutritionRows' => $this->nutritionRows(),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function summaryRows(): array
    {
        $checkin = $this->weeklyCheckin;

        $rows = [
            'Week' => $checkin->week_start_date->format('d-m-Y'),
            'Training' => "{$this->value($checkin->strength_sessions)} kracht, {$this->value($checkin->conditioning_sessions)} conditie/pickup, {$this->value($checkin->mobility_sessions)} preventie/mobiliteit",
            'Herstel' => 'Slaap '.$this->value($checkin->sleep_avg_hours).' uur, energie '.$this->score($checkin->energy_score).', spierpijn '.$checkin->sorenessDisplay(),
            'Rustdag' => $this->booleanLabel($checkin->had_full_rest_day),
            'Pijn' => $this->painSummary(),
            'Belasting' => 'Minuten '.$this->value($checkin->total_training_minutes).', hoogste RPE '.$this->score($checkin->highest_session_rpe ?? $checkin->rpe_highest).', load '.$this->value($checkin->calculated_training_load),
        ];

        if ($checkin->missed_target_reason) {
            $rows['Waarom niet gelukt'] = trim($checkin->missed_target_reason.' '.($checkin->missed_target_reason_other ? '- '.$checkin->missed_target_reason_other : ''));
        }

        if ($checkin->player->isGuardDevelopment()) {
            $rows['Guard werk'] = "{$this->value($checkin->handle_minutes)} handle-minuten, {$this->value($checkin->handle_sessions)} handle/passing sessies, {$this->value($checkin->pickup_sessions)} pickups, {$this->value($checkin->conditioning_minutes)} conditieminuten";
            $rows['Guard defence/playbook'] = "{$this->value($checkin->defence_sessions)} defence-blokken, {$this->value($checkin->playbook_calls_learned)} calls";

            if ($checkin->handles_worked_on) {
                $rows['Handles geoefend'] = $checkin->handles_worked_on;
            }

            if ($checkin->playbook_focus) {
                $rows['Playbook focus'] = $checkin->playbook_focus;
            }

            if ($checkin->attendance_notes) {
                $rows['Aanwezigheid'] = $checkin->attendance_notes;
            }

            if ($checkin->absence_communication_notes) {
                $rows['Communicatie'] = $checkin->absence_communication_notes;
            }
        }

        if ($checkin->notes) {
            $rows['Opmerking speler'] = $checkin->notes;
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function nutritionRows(): array
    {
        $checkin = $this->weeklyCheckin;

        if (! $checkin->player->tracksNutrition()) {
            return [];
        }

        $rows = [
            'Gewicht' => $this->value($checkin->weight_kg).' kg',
            'Gem. kcal' => $this->value($checkin->kcal_avg),
            'Eiwitstatus' => $this->proteinStatusLabel($checkin->protein_status),
            'Gem. eiwit' => $checkin->protein_avg_grams !== null ? $checkin->protein_avg_grams.'g/dag' : 'n.v.t.',
            'Eiwitdoel dagen' => $checkin->protein_target_days !== null ? $checkin->protein_target_days.'/7' : 'n.v.t.',
            'Eetlust' => $this->score($checkin->appetite_score),
        ];

        if ($checkin->protein_notes) {
            $rows['Eiwittoelichting'] = $checkin->protein_notes;
        }

        return $rows;
    }

    private function painSummary(): string
    {
        if (! $this->weeklyCheckin->pain) {
            return 'Nee';
        }

        return trim('Ja - '.($this->weeklyCheckin->pain_location ?: 'locatie niet ingevuld').($this->weeklyCheckin->pain_notes ? '. '.$this->weeklyCheckin->pain_notes : ''));
    }

    private function proteinStatusLabel(?string $status): string
    {
        return [
            'yes' => 'Ja (6-7 dagen)',
            'partial' => 'Soms (3-5 dagen)',
            'no' => 'Nee (0-2 dagen)',
        ][$status] ?? 'n.v.t.';
    }

    private function booleanLabel(?bool $value): string
    {
        if ($value === null) {
            return 'n.v.t.';
        }

        return $value ? 'Ja' : 'Nee';
    }

    private function score(mixed $value): string
    {
        return $value === null ? 'n.v.t.' : $value.'/10';
    }

    private function value(mixed $value): string
    {
        return $value === null ? 'n.v.t.' : (string) $value;
    }
}
