<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTemplate extends Model
{
    protected $fillable = ['type', 'name', 'description', 'goal', 'sort_order', 'training_program_pdf_path'];

    /**
     * @return array<string, array{name:string,description:string,goal:string,sort_order:int}>
     */
    public static function defaultRows(): array
    {
        return [
            Player::Conditioning => [
                'name' => 'Trainingstype A: Conditie',
                'description' => 'PDF-basis met extra aandacht voor basketbalgerichte conditie, herhaald sprintvermogen, herstelvermogen en explosiviteit.',
                'goal' => '2x kracht, 2x conditie/pickup, 3x 8 minuten blessurepreventie en minimaal 1 volledige rustdag per week.',
                'sort_order' => 1,
            ],
            Player::MuscleGain => [
                'name' => 'Trainingstype B: Bulk, kracht en spiermassa',
                'description' => 'Persoonlijk spiermassa-traject voor spelers die kracht, massa en belastbaarheid moeten opbouwen. Gewicht is een meetmiddel; sterker, explosief en blessurevrij blijven is leidend.',
                'goal' => '3x kracht, maandagpickup als basketbalconditie, maximaal 1 korte extra prikkel als pickup wegvalt, 3x preventie, minimaal 3000 kcal, 120-130g eiwit en 66-68 kg richting 17 augustus.',
                'sort_order' => 2,
            ],
            Player::Maintenance => [
                'name' => 'Trainingstype C: Conditie en kracht onderhoud',
                'description' => 'Fit blijven, conditie onderhouden, kracht behouden en fris klaar zijn voor 17 augustus.',
                'goal' => '2x kracht, 2x conditie/pickup, 3x 8 minuten blessurepreventie en minimaal 1 volledige rustdag per week.',
                'sort_order' => 3,
            ],
            Player::GuardDevelopment => [
                'name' => 'Trainingstype D: Guard development',
                'description' => 'Persoonlijk guard-traject richting de 1 met commitment, handles onder druk, passing, defence, playcalling, conditie/kracht en voeding als meetpunten.',
                'goal' => '3x handles/passing, 2x kracht, 2x conditie/pickup, 2x defence/first-step, 1x playbook/calls en wekelijkse communicatie over aanwezigheid.',
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @return Collection<int, self>
     */
    public static function ensureDefaults(): Collection
    {
        foreach (self::defaultRows() as $type => $attributes) {
            self::query()->firstOrCreate(['type' => $type], $attributes);
        }

        return self::query()->orderBy('sort_order')->get();
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProgramPhase::class)->orderBy('sort_order');
    }
}
