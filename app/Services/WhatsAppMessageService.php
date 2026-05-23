<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Collection;

class WhatsAppMessageService
{
    /**
     * @param  array{status:string, reason:string, advice:string, next_action:string, compliance:int}  $evaluation
     */
    public function forPlayer(Player $player, array $evaluation): string
    {
        return match (true) {
            str_contains($evaluation['reason'], 'Geen check-in') || str_contains($evaluation['reason'], 'mist') => $this->checkinReminder($player),
            $evaluation['status'] === 'green' => $this->compliment($player),
            str_contains($evaluation['reason'], 'Pijn') => $this->injuryFollowUp($player, $evaluation),
            $player->isMuscleGain() && (str_contains($evaluation['advice'], 'kcal') || str_contains($evaluation['advice'], 'eiwit')) => $this->bulkAdvice($player, $evaluation),
            default => $this->trainingAdjustment($player, $evaluation),
        };
    }

    public function checkinReminder(Player $player): string
    {
        return "Hoi {$player->name}, wil je je weekcheck nog invullen? Dan kan ik goed zien hoe je ervoor staat richting 17 augustus.";
    }

    public function compliment(Player $player): string
    {
        return "Lekker bezig {$player->name}. Je ligt op schema. Houd dit ritme vast en blijf eerlijk melden hoe energie, slaap en eventuele pijntjes voelen.";
    }

    /**
     * @param  array{next_action:string, advice:string}  $evaluation
     */
    public function trainingAdjustment(Player $player, array $evaluation): string
    {
        return "Hoi {$player->name}, kleine bijsturing deze week: {$evaluation['next_action']} {$evaluation['advice']}";
    }

    /**
     * @param  array{next_action:string, advice:string}  $evaluation
     */
    public function bulkAdvice(Player $player, array $evaluation): string
    {
        return "Hoi {$player->name}, bulk-focus voor deze week: {$evaluation['next_action']} Check elke avond rond 20:00 wat nog openstaat qua kcal en eiwit.";
    }

    /**
     * @param  array{next_action:string, advice:string}  $evaluation
     */
    public function injuryFollowUp(Player $player, array $evaluation): string
    {
        return "Hoi {$player->name}, je hebt pijn gemeld. {$evaluation['next_action']} Train niet door alsof er niets is; stuur me even waar je precies last van hebt.";
    }

    /**
     * @param  Collection<int, Player>  $players
     */
    public function groupCheckinReminder(Collection $players): string
    {
        $names = $players->pluck('name')->join(', ', ' en ');

        return "Reminder mannen: vul vandaag je U22 weekcheck in. Ik mis hem nog van {$names}. Kost kort tijd en helpt om slim bij te sturen richting 17 augustus.";
    }
}
