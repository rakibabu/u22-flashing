<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\WeeklyCheckin;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('app:move-weekly-checkin-week
    {player : Player ID, exacte spelernaam, of e-mail van de speler}
    {from_week : Bronweek, bijvoorbeeld 2026-06-01 of 2026-W23}
    {to_week : Doelweek, bijvoorbeeld 2026-05-25 of 2026-W22}
    {--dry-run : Toon wat er zou wijzigen zonder de database aan te passen}')]
#[Description('Verplaats een weekcheck veilig naar een andere week')]
class MoveWeeklyCheckinWeek extends Command
{
    public function handle(): int
    {
        $player = $this->findPlayer((string) $this->argument('player'));

        if (! $player) {
            return self::FAILURE;
        }

        try {
            $fromWeek = $this->parseWeek((string) $this->argument('from_week'));
            $toWeek = $this->parseWeek((string) $this->argument('to_week'));
        } catch (\Throwable $exception) {
            $this->error('Gebruik een geldige week, zoals 2026-06-01 of 2026-W23.');

            return self::FAILURE;
        }

        if ($fromWeek->isSameDay($toWeek)) {
            $this->error('De bronweek en doelweek zijn hetzelfde.');

            return self::FAILURE;
        }

        $source = WeeklyCheckin::query()
            ->whereBelongsTo($player)
            ->whereDate('week_start_date', $fromWeek->toDateString())
            ->first();

        if (! $source) {
            $this->error("Geen weekcheck gevonden voor {$player->name} in de week {$fromWeek->toDateString()}.");

            return self::FAILURE;
        }

        $target = WeeklyCheckin::query()
            ->whereBelongsTo($player)
            ->whereDate('week_start_date', $toWeek->toDateString())
            ->first();

        if ($target) {
            $this->error("Er bestaat al een weekcheck voor {$player->name} in de week {$toWeek->toDateString()} (ID {$target->id}).");
            $this->line('Controleer deze records handmatig voordat je samenvoegt of verwijdert.');

            return self::FAILURE;
        }

        $this->line("Speler: {$player->name} (ID {$player->id})");
        $this->line("Weekcheck ID: {$source->id}");
        $this->line('Submitted at: '.($source->submitted_at?->toDateTimeString() ?? 'niet verstuurd'));
        $this->line("Verplaatsing: {$fromWeek->toDateString()} -> {$toWeek->toDateString()}");

        if ($this->option('dry-run')) {
            $this->info('Dry run: er is niets aangepast.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($player, $fromWeek, $toWeek): void {
            $source = WeeklyCheckin::query()
                ->whereBelongsTo($player)
                ->whereDate('week_start_date', $fromWeek->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            $targetExists = WeeklyCheckin::query()
                ->whereBelongsTo($player)
                ->whereDate('week_start_date', $toWeek->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($targetExists) {
                $this->fail("Er bestaat inmiddels al een weekcheck voor {$player->name} in de week {$toWeek->toDateString()}.");
            }

            $source->update([
                'week_start_date' => $toWeek->toDateString(),
            ]);
        });

        $this->info('Weekcheck verplaatst.');

        return self::SUCCESS;
    }

    private function findPlayer(string $identifier): ?Player
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            $this->error('Geef een speler ID, spelernaam, of e-mail op.');

            return null;
        }

        $players = Player::query()
            ->with('user')
            ->where(function ($query) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $query->orWhereKey((int) $identifier);
                }

                $query
                    ->orWhere('name', $identifier)
                    ->orWhereHas('user', fn ($query) => $query->where('email', $identifier));
            })
            ->limit(2)
            ->get();

        if ($players->isEmpty()) {
            $this->error("Geen speler gevonden voor '{$identifier}'.");

            return null;
        }

        if ($players->count() > 1) {
            $this->error("Meerdere spelers gevonden voor '{$identifier}'. Gebruik het player ID.");

            return null;
        }

        return $players->first();
    }

    private function parseWeek(string $week): CarbonInterface
    {
        $week = trim($week);

        if (preg_match('/^(?<year>\d{4})-W(?<week>\d{2})$/i', $week, $matches) === 1) {
            return Carbon::now()
                ->setISODate((int) $matches['year'], (int) $matches['week'])
                ->startOfWeek();
        }

        return Carbon::parse($week)->startOfWeek();
    }
}
