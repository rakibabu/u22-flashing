<?php

namespace Database\Seeders;

use App\Models\CoachNote;
use App\Models\ExerciseLibraryItem;
use App\Models\Invite;
use App\Models\Player;
use App\Models\ProgramTemplate;
use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CoachUserSeeder::class);

        $templates = $this->seedProgramTemplates();
        $this->seedExerciseLibrary();
        $players = $this->seedPlayers();
        $this->seedCheckins($players);
        $this->seedNotes($players);

        foreach ($players as $player) {
            if (! $player->user_id && ! $player->latestInvite()->exists()) {
                Invite::createForPlayer($player);
            }
        }
    }

    /**
     * @return array<string, ProgramTemplate>
     */
    private function seedProgramTemplates(): array
    {
        $data = [
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
        ];

        $templates = [];

        foreach ($data as $type => $attributes) {
            $template = ProgramTemplate::query()->updateOrCreate(['type' => $type], $attributes);
            $templates[$type] = $template;

            foreach ($this->phases() as $phase) {
                $template->phases()->updateOrCreate(['sort_order' => $phase['sort_order']], $phase);
            }
        }

        return $templates;
    }

    /**
     * @return array<int, array{name:string,start_date:?string,end_date:?string,description:string,sort_order:int}>
     */
    private function phases(): array
    {
        return [
            [
                'name' => 'Fase 0: 11 mei t/m 6 juni - pickup + kracht onderhouden',
                'start_date' => '2026-05-11',
                'end_date' => '2026-06-06',
                'description' => 'Ma pickup, di Gym A, wo Zone 2 30-35 min + mobiliteit, do pickup, vr Gym B, za optioneel shooting + 6x10 sec strides, zo vrij. Pickup telt als zware dag. Mis je pickup, kies C3 of C4, niet allebei extra.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Fase 1: 8 juni t/m 28 juni - basis bouwen',
                'start_date' => '2026-06-08',
                'end_date' => '2026-06-28',
                'description' => 'Ma Gym A, di C1 Zone 2 + versnellingen, wo Gym B, do C2 intervals of pickup, vr vrij/mobiliteit, za Gym C optioneel als je fris bent, zo vrij.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Fase 2: 29 juni t/m 26 juli - intensiteit omhoog',
                'start_date' => '2026-06-29',
                'end_date' => '2026-07-26',
                'description' => 'Ma Gym A, di C3 HIIT, wo Gym B, do C4 repeated sprint + change of direction of pickup, vr vrij, za Gym C power + onderhoud, zo vrij. Niet meer dan 2 zware high-intensity dagen stapelen.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Fase 3: 27 juli t/m 16 augustus - preseason-ready',
                'start_date' => '2026-07-27',
                'end_date' => '2026-08-16',
                'description' => '27 juli-2 aug fit en scherp, 3-9 aug wedstrijdspecifiek, 10-16 aug afbouwen. Laatste week: ma korte full-body, di sprint sharpness, do 20-30 min zone 2 + mobility, weekend rust/shooting. Vanaf donderdag 13 augustus geen zware lower-body meer.',
                'sort_order' => 3,
            ],
        ];
    }

    private function seedExerciseLibrary(): void
    {
        ExerciseLibraryItem::query()
            ->whereIn('name', ['Zone 2', 'Tempo runs', 'Basketball HIIT', 'Repeated sprint', 'Court block conditioning', 'Preventieblok'])
            ->delete();

        $items = [
            ['Programmaregels', 'Minimale weeknorm', '2x krachttraining, 2x conditieprikkel, 3x per week 8 minuten preventie/mobiliteit en minimaal 1 volledige rustdag.', 'Pickup telt mee als conditie/hoge intensiteit. Geen zware lower-body op de dag voor zware pickup of sprinttraining als je daar stijf van wordt.', 'Consistentie boven perfectie. Niet alles inhalen op 1 dag.'],
            ['Programmaregels', 'RPE-schaal', 'RPE 5-6 rustig, RPE 7 stevig controleerbaar, RPE 8 zwaar maar technisch netjes, RPE 9 bijna maximaal, RPE 10 alleen tests/specifieke sprints.', 'Gebruik RPE 9 kort en bewust, niet vaak. RPE 10 is geen standaard trainingsprikkel.', 'Maximale sprints zonder warming-up en trainen tot technisch verval.'],
            ['Programmaregels', 'Gym gewicht kiezen', 'Stop meestal met ongeveer 2 goede herhalingen over. Als alle sets 2 weken makkelijk voelen: +2,5 tot 5 kg of 1-2 reps extra.', 'Techniek bepaalt. Als techniek slechter wordt, gewicht omlaag.', 'Ego-lifting en rommelreps tellen niet.'],
            ['Programmaregels', 'Vakantie-minimum', 'Ondergrens bij weinig tijd: 1x Strength A, 1x HIIT 8-10 x 1 min hard/1 min rustig, 1x Strength B + 6 x 20 m sprint.', 'Daarnaast 2-3x per week het 8 minuten preventieblok.', 'Minder dan dit betekent waarschijnlijk kracht/conditie verliezen richting 17 augustus.'],
            ['Programmaregels', 'Laatste week voor 17 augustus', 'Ma 10 aug korte full-body, di 11 aug 6-8 korte sprints met volledige rust, do 13 aug 20-30 min zone 2 + mobility, weekend rust/shooting.', 'Vanaf donderdag 13 augustus geen zware lower-body sessies meer.', 'De laatste week nog extreem hard trainen.'],
            ['Gym', 'Gym A - Full body strength', 'Squat-variant 4x5-6, Romanian deadlift 3x6-8, DB bench/push-up 3x8, row 3x8-10, Bulgarian split squat 3x8/been, Pallof/dead bug 3x10/kant, calf raise 3x12 + tibialis 2x15.', 'Rust 90-150 sec bij zware sets. Werk gecontroleerd en houd ongeveer 2 goede reps over.', 'Romp slap, knieen naar binnen, trekken uit onderrug of tot failure gaan.'],
            ['Gym', 'Gym B - Unilateral strength + posterior chain', 'Trap bar/deadlift 4x4-5, step-up/reverse lunge 3x8/been, pull-up/lat pulldown 3x6-10, overhead press 3x6-8, hip thrust/hamstring bridge 3x8-10, Copenhagen light 2-3x15-25 sec/kant, Nordic assisted/slider curl 2-3x5.', 'Focus op heup/hamstring en eenbenige controle.', 'Knie instabiel, ronde rug, lies forceren of hamstringkwaliteit opofferen.'],
            ['Gym', 'Gym C - Power + onderhoud', 'Box/squat jump 4x3, med ball throw 4x4, front/goblet/split squat 3x5 RPE 6-7, kettlebell swing 3x6, press 3x8, row 3x8-10, farmer walk 4x20-30 m.', 'Explosief maar fris. Neem genoeg rust om explosief te blijven.', 'Doorgaan als sprongkwaliteit inzakt of power traag wordt.'],
            ['Buiten/bodyweight', 'Outdoor Strength A', 'Split squat 4x10/been, single-leg RDL met rugzak 3x10/been, push-ups 4 sets met 2 reps over, row 4x10-12, single-leg hip thrust 3x10/been, calf raises 3x15, side plank 3x30 sec/kant.', 'Gebruik dit als gym-alternatief op vakantie of zonder materiaal.', 'Haasten, rompspanning verliezen of push-ups tot complete failure.'],
            ['Buiten/bodyweight', 'Outdoor Strength B', 'Walking lunges 4x12/been, squat jump 4x4, backpack squat 4x12, pike push-up 3x8-12, hamstring walkouts 3x8, Copenhagen light 2x15-20 sec/kant, hollow/dead bug 3x30 sec.', 'Sterk genoeg als vakantie- of buitenvariant.', 'Slechte landingen en Copenhagen te zwaar maken.'],
            ['Conditie', 'C1 Zone 2', '30-45 minuten rustig bewegen op RPE 5-6. In fase 1: 30, 35 en 40 minuten met 6-10 korte versnellingen.', 'Versnellingen zijn 75-80%, geen maximale sprints. Wandel 60-90 sec tussendoor.', 'Rustige sessies stiekem hard maken.'],
            ['Conditie', 'C2 Intervals', 'Fase 1 intervals: 4x4 min RPE 7, daarna 5x4 min RPE 7, daarna 6x3 min RPE 7-8.', 'Kan buiten, fiets, loopband, rower of court. Neem 90 sec tot 2 min rust.', 'Te hard starten waardoor de laatste blokken instorten.'],
            ['Conditie', 'C3 HIIT', 'Fase 2 HIIT: 8-12 x 1 min hard / 1 min rustig. Deloadweek terug naar 8 herhalingen.', 'Hard is RPE 8: stevig tempo, geen sprint.', 'HIIT stapelen bovenop zware pickup.'],
            ['Conditie', 'C4 Repeated sprint + COD', '2-4 sets van 5-6 x 20 m sprint met 20-30 sec rust. Combineer met 5-10-5 shuttle, close-out/backpedal of defensive stance sprint.', 'Goed opwarmen. Elke sprint technisch strak.', 'Stop bij hamstring, kuit of lies die strak voelt of techniek die instort.'],
            ['Conditie', 'C5 Court block conditioning', '4 blokken van 5 min met 20 sec werk / 40 sec rustig: sprint, close-out, backpedal, shuffle, rebound jump.', 'Houd het basketbalachtig en technisch.', 'Kwaliteit laten zakken om alleen maar moe te worden.'],
            ['Blessurepreventie', 'Standaard warming-up', '10-12 minuten: raise, mobiliteit, activatie en basketbalvoorbereiding.', 'Ankle rocks, stretch/hip openers, glute bridge, dead bug, side plank, lateral walk, pogo jumps, lateral bound stick, acceleraties en close-out/backpedal.', 'Maximale sprints of zware sets zonder warming-up.'],
            ['Blessurepreventie', '8 minuten preventieblok', 'Ankle rocks 2x10/kant, single-leg balance reach 2x5/kant, calf raise langzaam 2x12, tibialis raise 2x15, Copenhagen light 2x15-20 sec/kant, hamstring bridge 2x10, lateral bound stick 2x5/kant.', 'Doe dit na warming-up of einde training, 3x per week.', 'Het blok als maximale workout behandelen of pijn negeren.'],
            ['Voeding', 'Spiermassa persoonlijke targets', 'Nu 60 kg. Richting 17 augustus is 66-68 kg goed en 68-70 kg een stretchdoel. Seizoensdoel 70-72 kg, lange termijn 75-80 kg.', 'Weeg 3x per week in de ochtend en stuur op het weekgemiddelde. Gewicht is een meetmiddel; gymkracht, explosiviteit en pijngeschiedenis blijven leidend.', 'Niet elke dag panieken om gewichtsschommelingen. Kijk naar het gemiddelde en de kwaliteit van training.'],
            ['Voeding', 'Bulk voedingsroutine', 'Dagelijks kcal tracken. Mijn Eetmeter standaard, YAZIO als backup. Rond 20:00 checken hoeveel kcal en eiwit nog openstaat.', 'Rust/licht 3000-3200 kcal, gymdag 3300-3400 kcal, maandagpickup 3600 kcal. Absoluut minimum is 3000 kcal.', 'Eerst eiwit fixen als 120-130g niet lukt. Vloeibare kcal helpen als eetlust laag is.'],
            ['Voeding', 'Bulk bijstuurregels', 'Als het weekgemiddelde 2 weken niet stijgt: +250 kcal per dag. Als gewicht sneller dan 1 kg/week stijgt en je voelt je trager: -150 tot -200 kcal of minder vet/suiker.', 'Gebruik makkelijke extra kcal: smoothie, volle kwark, pindakaas, noten, olijfolie, extra boterham of rijst/pasta.', 'Niet extra hard gaan lopen omdat je zwaarder wordt. Houd conditie kort en basketbalgericht.'],
            ['Voeding', 'Maandagpickup voeding', 'Pickupdag is 3600 kcal. Eet 2-3 uur vooraf een grote maaltijd en 60-90 minuten vooraf een kleine snack.', 'Binnen 60 minuten na pickup: smoothie, kwark of maaltijd met koolhydraten en eiwit.', 'Pickup doen op lege tank en daarna pas laat eten.'],
            ['Gym', 'Spiermassa Gym A - krachtbasis', 'Squat 4x5-6, DB bench/bench 4x6-8, cable/one-arm row 4x8-10, RDL 3x6-8, calf raise 3x12 + tibialis 2x15, Pallof/dead bug 3x10/kant.', 'RPE 7-8, meestal 1-2 goede reps over. Als alle sets top-range halen met nette techniek: kleine gewichtsstap.', 'Tot failure gaan of onderrug/kniecontrole verliezen.'],
            ['Gym', 'Spiermassa Gym B - posterior chain', 'Trap bar/deadlift 4x4-5, Bulgarian split squat 3x8/been, pull-up/lat pulldown 4x6-10, overhead press 3x6-8, hip thrust 3x8-10, Copenhagen light 2-3x15-25 sec, slider curl/assisted Nordic 2-3x5-8.', 'Bouw kracht en eenbenige controle zonder lies/hamstring te forceren.', 'Zware lower-body stapelen vlak voor pickup of sprintwerk.'],
            ['Gym', 'Spiermassa Gym C - hypertrophy + power', 'Box/squat jump 4x3, med ball throw/pass 4x4, leg press/goblet squat 3x10, DB incline press 3x10, row/pulldown 3x10, hamstring curl 3x10, farmer walk 4x20-30 m.', 'Power fris houden en daarna onderhoud/hypertrophy doen.', 'Sprongen blijven doen als ze traag of zwaar worden.'],
            ['Conditie', 'Spiermassa conditieregel', 'Maandagpickup telt als de hoofdprikkel. Als pickup stopt: 8x1 min hard/1 min easy of 10x30 sec court work/30 sec rust.', 'Optioneel 6x20 m sprint op zaterdag alleen als benen fris zijn. Stop bij hamstring, kuit of lies die strak voelt.', 'Extra veel duurloop toevoegen terwijl gewicht en kracht moeten stijgen.'],
        ];

        foreach ($items as $index => [$category, $name, $description, $execution, $cues]) {
            ExerciseLibraryItem::query()->updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'description' => $description,
                    'execution' => $execution,
                    'coaching_cues' => $cues,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    /**
     * @return Collection<int, Player>
     */
    private function seedPlayers()
    {
        $rows = [
            ['Daan Conditie', Player::Conditioning, ['strength' => 2, 'conditioning' => 2, 'mobility' => 3, 'monday' => true, 'thursday' => true]],
            ['Sem Conditie', Player::Conditioning, ['strength' => 2, 'conditioning' => 2, 'mobility' => 3, 'monday' => true, 'thursday' => true]],
            ['Milan Bulk', Player::MuscleGain, ['strength' => 3, 'conditioning' => 1, 'mobility' => 3, 'monday' => true, 'thursday' => false]],
            ['Jay Onderhoud', Player::Maintenance, ['strength' => 2, 'conditioning' => 2, 'mobility' => 3, 'monday' => true, 'thursday' => true]],
            ['Noah Onderhoud', Player::Maintenance, ['strength' => 2, 'conditioning' => 2, 'mobility' => 3, 'monday' => true, 'thursday' => true]],
            ['Levi Onderhoud', Player::Maintenance, ['strength' => 2, 'conditioning' => 2, 'mobility' => 3, 'monday' => true, 'thursday' => true]],
        ];

        return collect($rows)->map(function (array $row): Player {
            [$name, $type, $settings] = $row;

            $player = Player::query()->updateOrCreate(
                ['name' => $name],
                [
                    'program_type' => $type,
                    'active' => true,
                    'age' => $type === Player::MuscleGain ? 21 : 19,
                    'height_cm' => $type === Player::MuscleGain ? 183 : null,
                    'start_weight_kg' => $type === Player::MuscleGain ? 60 : null,
                    'target_weight_kg' => $type === Player::MuscleGain ? 68 : null,
                    'long_term_target_weight_kg' => $type === Player::MuscleGain ? 80 : null,
                    'notes' => $type === Player::MuscleGain ? '21 jaar, 1.83 m, 60 kg. Eet weinig vlees, wel kip. Maandag pickup, geen donderdagpickup. 66-68 kg richting 17 augustus is goed, 68-70 kg stretch, 70-72 kg seizoensdoel en 75-80 kg lange termijn.' : null,
                ],
            );

            $player->settings()->updateOrCreate(
                ['player_id' => $player->id],
                [
                    'strength_target_per_week' => $settings['strength'],
                    'conditioning_target_per_week' => $settings['conditioning'],
                    'mobility_target_per_week' => $settings['mobility'],
                    'kcal_rest_day' => $type === Player::MuscleGain ? 3200 : null,
                    'kcal_training_day' => $type === Player::MuscleGain ? 3400 : null,
                    'kcal_pickup_day' => $type === Player::MuscleGain ? 3600 : null,
                    'kcal_minimum' => $type === Player::MuscleGain ? 3000 : null,
                    'protein_target_min' => $type === Player::MuscleGain ? 120 : null,
                    'protein_target_max' => $type === Player::MuscleGain ? 130 : null,
                    'pickup_monday_expected' => $settings['monday'],
                    'pickup_thursday_expected' => $settings['thursday'],
                    'uses_mijn_eetmeter' => $type === Player::MuscleGain,
                    'uses_yazio_backup' => $type === Player::MuscleGain,
                    'notes' => $type === Player::MuscleGain ? 'Persoonlijk spiermassa-plan: 3 vaste gymmomenten, maandagpickup als basketbalconditie, geen donderdagpickup, 3000 kcal minimum, 3300-3400 kcal op gymdagen, 3600 kcal op pickupdag, 120-130g eiwit, 3x per week ochtendgewicht en elke avond rond 20:00 kcal/eiwit checken.' : null,
                ],
            );

            return $player;
        });
    }

    private function seedCheckins($players): void
    {
        foreach ($players as $index => $player) {
            foreach ([now()->startOfWeek()->subWeek(), now()->startOfWeek()] as $weekIndex => $week) {
                if ($player->name === 'Levi Onderhoud' && $weekIndex === 1) {
                    continue;
                }

                WeeklyCheckin::query()->updateOrCreate(
                    ['player_id' => $player->id, 'week_start_date' => $week->toDateString()],
                    [
                        'weight_kg' => $player->isMuscleGain() ? 60 + ($weekIndex * 0.1) : null,
                        'strength_sessions' => $player->isMuscleGain() ? 2 : ($index % 2 ? 1 : 2),
                        'conditioning_sessions' => $player->isConditioning() ? 2 : 1,
                        'mobility_sessions' => 2,
                        'pickup_monday' => true,
                        'pickup_thursday' => ! $player->isMuscleGain(),
                        'had_full_rest_day' => $index !== 4,
                        'sleep_avg_hours' => $index === 4 ? 6.5 : 7.5,
                        'energy_score' => $index === 1 ? 4 : 7,
                        'soreness_score' => $index === 1 ? 8 : 4,
                        'pain' => $index === 0 && $weekIndex === 1,
                        'pain_location' => $index === 0 && $weekIndex === 1 ? 'rechterknie' : null,
                        'rpe_highest' => $player->isConditioning() ? 8 : null,
                        'total_training_minutes' => 180 + ($index * 10),
                        'highest_session_rpe' => $player->isConditioning() ? 8 : 7,
                        'calculated_training_load' => (180 + ($index * 10)) * ($player->isConditioning() ? 8 : 7),
                        'missed_target_reason' => $index % 2 ? 'geen tijd' : null,
                        'kcal_avg' => $player->isMuscleGain() ? 3050 : null,
                        'protein_status' => $player->isMuscleGain() ? 'partial' : null,
                        'protein_avg_grams' => $player->isMuscleGain() ? 105 + ($weekIndex * 5) : null,
                        'protein_target_days' => $player->isMuscleGain() ? 4 + $weekIndex : null,
                        'protein_notes' => $player->isMuscleGain() ? 'Ontbijt en lunch lukken, avond nog wisselend qua eiwit.' : null,
                        'appetite_score' => $player->isMuscleGain() ? 6 : null,
                        'used_mijn_eetmeter' => $player->isMuscleGain(),
                        'used_yazio' => false,
                        'notes' => $player->isMuscleGain() ? 'Krijg ontbijt lastig weg.' : 'Prima week.',
                        'submitted_at' => now(),
                    ],
                );
            }
        }
    }

    private function seedNotes($players): void
    {
        $coach = User::query()->where('email', CoachUserSeeder::EMAIL)->firstOrFail();

        foreach ($players->take(2) as $player) {
            CoachNote::query()->updateOrCreate(
                ['player_id' => $player->id, 'title' => 'Weekfocus'],
                [
                    'coach_user_id' => $coach->id,
                    'type' => 'advice',
                    'body' => 'Blijf eerlijk checken en plan je sessies vooruit.',
                    'visible_to_player' => true,
                    'week_start_date' => now()->startOfWeek()->toDateString(),
                ],
            );
        }
    }
}
