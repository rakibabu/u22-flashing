<?php

namespace App\Livewire\Player;

use App\Models\Player;
use App\Models\WeeklyCheckin;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Checkin extends Component
{
    use AuthorizesRequests;

    public array $form = [
        'weight_kg' => null,
        'strength_sessions' => null,
        'conditioning_sessions' => null,
        'mobility_sessions' => null,
        'pickup_monday' => null,
        'pickup_thursday' => null,
        'had_full_rest_day' => false,
        'sleep_avg_hours' => null,
        'energy_score' => null,
        'soreness_score' => null,
        'pain' => false,
        'pain_location' => null,
        'pain_notes' => null,
        'rpe_highest' => null,
        'total_training_minutes' => null,
        'highest_session_rpe' => null,
        'calculated_training_load' => null,
        'missed_target_reason' => null,
        'missed_target_reason_other' => null,
        'kcal_avg' => null,
        'protein_status' => null,
        'protein_avg_grams' => null,
        'protein_target_days' => null,
        'protein_notes' => null,
        'appetite_score' => null,
        'used_mijn_eetmeter' => null,
        'used_yazio' => null,
        'notes' => null,
    ];

    public bool $saved = false;

    public bool $autosaved = false;

    public ?string $autosavedAt = null;

    public ?string $stepError = null;

    public int $step = 1;

    public function mount(): void
    {
        $player = auth()->user()->player;
        abort_unless($player, 403);

        $checkin = $player->checkins()->whereDate('week_start_date', now()->startOfWeek()->toDateString())->first();

        if ($checkin) {
            $this->authorize('update', $checkin);
            $this->form = array_merge($this->form, $checkin->only(array_keys($this->form)));
        }
    }

    public function nextStep(): void
    {
        try {
            $this->validate($this->rulesForStep($this->step), $this->messages(), $this->validationAttributes());
        } catch (ValidationException $exception) {
            $this->stepError = $this->stepErrorMessage($this->step);

            throw $exception;
        }

        $this->stepError = null;
        $this->step = min($this->step + 1, $this->maxStep());
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        $this->step = max(1, min($step, $this->maxStep()));
    }

    public function updatedForm(mixed $value, ?string $key = null): void
    {
        if (! is_string($key) || ! array_key_exists($key, $this->form)) {
            return;
        }

        if ($value === '') {
            $this->form[$key] = null;
        }

        $this->stepError = null;

        if ($key === 'protein_target_days') {
            $this->form['protein_status'] = $this->proteinStatusFromDays($this->form['protein_target_days']);
        }

        $this->autosaveField($key);
    }

    public function save(): void
    {
        $player = $this->currentPlayer();
        abort_unless($player, 403);

        try {
            $validated = $this->validate($this->rules($player), $this->messages(), $this->validationAttributes())['form'];
        } catch (ValidationException $exception) {
            $this->stepError = 'Vul de ontbrekende velden in voordat je de weekcheck verstuurt.';

            throw $exception;
        }

        $validated = $this->normalizeForProgram($validated, $player);

        $validated['calculated_training_load'] = $this->trainingLoad(
            minutes: $validated['total_training_minutes'] ?? null,
            rpe: $validated['highest_session_rpe'] ?? null,
        );

        if (($validated['highest_session_rpe'] ?? null) !== null) {
            $validated['rpe_highest'] = $validated['highest_session_rpe'];
        }

        $checkin = WeeklyCheckin::query()
            ->where('player_id', $player->id)
            ->whereDate('week_start_date', now()->startOfWeek()->toDateString())
            ->first();

        if ($checkin) {
            $this->authorize('update', $checkin);
            $checkin->update($validated + ['submitted_at' => now()]);
        } else {
            $this->authorize('create', WeeklyCheckin::class);
            WeeklyCheckin::query()->create($validated + [
                'player_id' => $player->id,
                'week_start_date' => now()->startOfWeek(),
                'submitted_at' => now(),
            ]);
        }

        $this->saved = true;
    }

    public function maxStep(): int
    {
        return $this->currentPlayer()->program_type === Player::Maintenance ? 3 : 4;
    }

    public function render()
    {
        $player = $this->currentPlayer();

        return view('livewire.player.checkin', [
            'player' => $player,
            'maxStep' => $this->maxStep(),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Player $player = null): array
    {
        $player ??= $this->currentPlayer();

        return $this->requiredRules($this->baseRules($player), $player);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function baseRules(?Player $player = null): array
    {
        return [
            'form.weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.strength_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.conditioning_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.mobility_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.pickup_monday' => ['nullable', 'boolean'],
            'form.pickup_thursday' => ['nullable', 'boolean'],
            'form.had_full_rest_day' => ['nullable', 'boolean'],
            'form.sleep_avg_hours' => ['nullable', 'numeric', 'between:0,12'],
            'form.energy_score' => ['nullable', 'integer', 'between:1,10'],
            'form.soreness_score' => ['nullable', 'integer', 'between:1,10'],
            'form.pain' => ['boolean'],
            'form.pain_location' => ['nullable', 'string', 'max:255'],
            'form.pain_notes' => ['nullable', 'string', 'max:2000'],
            'form.rpe_highest' => ['nullable', 'integer', 'between:1,10'],
            'form.total_training_minutes' => ['nullable', 'integer', 'between:0,2000'],
            'form.highest_session_rpe' => ['nullable', 'integer', 'between:1,10'],
            'form.calculated_training_load' => ['nullable', 'integer', 'min:0'],
            'form.missed_target_reason' => ['nullable', Rule::in($this->missedTargetReasons())],
            'form.missed_target_reason_other' => ['nullable', 'string', 'max:255'],
            'form.kcal_avg' => ['nullable', 'integer', 'between:1000,6000'],
            'form.protein_status' => ['nullable', Rule::in(['yes', 'partial', 'no'])],
            'form.protein_avg_grams' => ['nullable', 'integer', 'between:0,250'],
            'form.protein_target_days' => ['nullable', 'integer', 'between:0,7'],
            'form.protein_notes' => ['nullable', 'string', 'max:1000'],
            'form.appetite_score' => ['nullable', 'integer', 'between:1,10'],
            'form.used_mijn_eetmeter' => ['nullable', 'boolean'],
            'form.used_yazio' => ['nullable', 'boolean'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, array<int, mixed>>
     */
    private function requiredRules(array $rules, Player $player): array
    {
        $rules['form.strength_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.conditioning_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.mobility_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.sleep_avg_hours'] = ['required', 'numeric', 'between:0,12'];
        $rules['form.energy_score'] = ['required', 'integer', 'between:1,10'];
        $rules['form.soreness_score'] = ['required', 'integer', 'between:1,10'];
        $rules['form.pain_location'] = [Rule::requiredIf((bool) ($this->form['pain'] ?? false)), 'nullable', 'string', 'max:255'];
        $rules['form.highest_session_rpe'] = [Rule::requiredIf($player->isConditioning() && (int) ($this->form['conditioning_sessions'] ?? 0) > 0), 'nullable', 'integer', 'between:1,10'];
        $rules['form.missed_target_reason'] = [Rule::requiredIf($this->isUnderTarget($player)), 'nullable', Rule::in($this->missedTargetReasons())];
        $rules['form.missed_target_reason_other'] = [Rule::requiredIf($this->isUnderTarget($player) && ($this->form['missed_target_reason'] ?? null) === 'anders'), 'nullable', 'string', 'max:255'];

        if ($player->isMuscleGain()) {
            $rules['form.weight_kg'] = ['required', 'numeric', 'between:40,160'];
            $rules['form.kcal_avg'] = ['required', 'integer', 'between:1000,6000'];
            $rules['form.protein_status'] = ['nullable', Rule::in(['yes', 'partial', 'no'])];
            $rules['form.protein_avg_grams'] = [Rule::requiredIf($this->hasIncompleteProtein()), 'nullable', 'integer', 'between:0,250'];
            $rules['form.protein_target_days'] = ['required', 'integer', 'between:0,7'];
            $rules['form.protein_notes'] = [Rule::requiredIf($this->hasIncompleteProtein()), 'nullable', 'string', 'max:1000'];
            $rules['form.appetite_score'] = ['required', 'integer', 'between:1,10'];
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function autosaveRules(Player $player): array
    {
        return Arr::except($this->baseRules($player), [
            'form.calculated_training_load',
        ]);
    }

    private function autosaveField(string $key): void
    {
        $player = $this->currentPlayer();
        $rules = $this->autosaveRules($player);
        $ruleKey = "form.{$key}";

        if (! array_key_exists($ruleKey, $rules)) {
            return;
        }

        $this->validate(
            [$ruleKey => $rules[$ruleKey]],
            $this->messages(),
            $this->validationAttributes(),
        );

        $payload = $this->autosavePayloadFor($key, $player);

        if ($payload === []) {
            return;
        }

        $checkin = WeeklyCheckin::query()
            ->where('player_id', $player->id)
            ->whereDate('week_start_date', now()->startOfWeek()->toDateString())
            ->first();

        if ($checkin) {
            $this->authorize('update', $checkin);
            $checkin->update($payload);
        } else {
            $this->authorize('create', WeeklyCheckin::class);
            WeeklyCheckin::query()->create($payload + [
                'player_id' => $player->id,
                'week_start_date' => now()->startOfWeek(),
            ]);
        }

        $this->autosaved = true;
        $this->autosavedAt = now()->format('H:i');
    }

    /**
     * @return array<string, mixed>
     */
    private function autosavePayloadFor(string $key, Player $player): array
    {
        if ($this->shouldIgnoreAutosaveField($key, $player)) {
            return [];
        }

        $value = $this->form[$key] ?? null;

        if ($value === '') {
            $value = null;
        }

        $payload = [$key => $this->autosaveDatabaseValue($key, $value)];

        if ($key === 'protein_target_days') {
            $payload['protein_status'] = $this->proteinStatusFromDays($value);
        }

        if (in_array($key, ['total_training_minutes', 'highest_session_rpe'], true)) {
            $payload['calculated_training_load'] = $this->trainingLoad(
                minutes: $this->nullableInt($this->form['total_training_minutes'] ?? null),
                rpe: $this->nullableInt($this->form['highest_session_rpe'] ?? null),
            );
        }

        if ($key === 'highest_session_rpe') {
            $payload['rpe_highest'] = $this->nullableInt($value);
        }

        $proteinIsComplete = $key === 'protein_target_days'
            ? ! $this->hasIncompleteProtein($this->proteinStatusFromDays($value))
            : ! $this->hasIncompleteProtein(is_string($value) ? $value : null);

        if (in_array($key, ['protein_status', 'protein_target_days'], true) && $proteinIsComplete) {
            $payload['protein_avg_grams'] = null;
            $payload['protein_notes'] = null;

            if ($key === 'protein_status') {
                $payload['protein_target_days'] = null;
                $this->form['protein_target_days'] = null;
            }

            $this->form['protein_avg_grams'] = null;
            $this->form['protein_notes'] = null;
        }

        if ($key === 'missed_target_reason' && $value !== 'anders') {
            $payload['missed_target_reason_other'] = null;
            $this->form['missed_target_reason_other'] = null;
        }

        if (in_array($key, ['strength_sessions', 'conditioning_sessions', 'mobility_sessions', 'kcal_avg', 'protein_status', 'protein_target_days'], true) && ! $this->isUnderTarget($player)) {
            $payload['missed_target_reason'] = null;
            $payload['missed_target_reason_other'] = null;
            $this->form['missed_target_reason'] = null;
            $this->form['missed_target_reason_other'] = null;
        }

        return $payload;
    }

    private function shouldIgnoreAutosaveField(string $key, Player $player): bool
    {
        $muscleGainOnlyFields = [
            'weight_kg',
            'kcal_avg',
            'protein_status',
            'protein_avg_grams',
            'protein_target_days',
            'protein_notes',
            'appetite_score',
            'used_mijn_eetmeter',
            'used_yazio',
        ];

        if (! $player->isMuscleGain() && in_array($key, $muscleGainOnlyFields, true)) {
            return true;
        }

        return $player->isMuscleGain() && $key === 'pickup_thursday';
    }

    private function autosaveDatabaseValue(string $key, mixed $value): mixed
    {
        if ($key === 'pain' && $value === null) {
            return false;
        }

        return $value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesForStep(int $step): array
    {
        $player = $this->currentPlayer();
        $rules = $this->rules($player);

        if ($step === 1) {
            return Arr::only($rules, [
                'form.strength_sessions',
                'form.conditioning_sessions',
                'form.mobility_sessions',
                'form.pickup_monday',
                'form.pickup_thursday',
                'form.had_full_rest_day',
            ]);
        }

        if ($step === 2) {
            return Arr::only($rules, [
                'form.sleep_avg_hours',
                'form.energy_score',
                'form.soreness_score',
                'form.pain',
                'form.pain_location',
                'form.pain_notes',
            ]);
        }

        if ($step === 3 && $player->isMuscleGain()) {
            return Arr::only($rules, [
                'form.weight_kg',
                'form.kcal_avg',
                'form.protein_status',
                'form.protein_avg_grams',
                'form.protein_target_days',
                'form.protein_notes',
                'form.appetite_score',
                'form.used_mijn_eetmeter',
                'form.used_yazio',
            ]);
        }

        if ($step === 3 && $player->isConditioning()) {
            return Arr::only($rules, [
                'form.total_training_minutes',
                'form.highest_session_rpe',
            ]);
        }

        return Arr::only($rules, [
            'form.missed_target_reason',
            'form.missed_target_reason_other',
            'form.notes',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'form.missed_target_reason.required' => 'Kies waarom het niet gelukt is.',
            'form.missed_target_reason_other.required' => 'Vul kort in wat de andere reden is.',
            'form.pain_location.required' => 'Vul in waar de pijn zit.',
            'form.weight_kg.required' => 'Vul je gewicht in.',
            'form.kcal_avg.required' => 'Vul je gemiddelde kcal per dag in.',
            'form.protein_status.required' => 'Geef aan of je je eiwit hebt gehaald.',
            'form.protein_avg_grams.required' => 'Vul in hoeveel gram eiwit je gemiddeld haalde.',
            'form.protein_target_days.required' => 'Kies hoeveel dagen je het eiwitdoel haalde.',
            'form.protein_notes.required' => 'Vul kort in wat er wel of niet lukte met eiwit.',
            'form.appetite_score.required' => 'Vul je eetlustscore in.',
            'form.highest_session_rpe.required' => 'Vul de zwaarte van je zwaarste sessie in.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'form.strength_sessions' => 'krachttrainingen',
            'form.conditioning_sessions' => 'conditietrainingen',
            'form.mobility_sessions' => 'preventie/mobiliteit',
            'form.sleep_avg_hours' => 'slaap',
            'form.energy_score' => 'energie',
            'form.soreness_score' => 'vermoeidheid/spierpijn',
            'form.total_training_minutes' => 'trainingsminuten',
            'form.highest_session_rpe' => 'zwaarste sessie',
            'form.weight_kg' => 'gewicht',
            'form.kcal_avg' => 'kcal',
            'form.protein_status' => 'eiwit',
            'form.protein_avg_grams' => 'gemiddelde eiwitgrammen',
            'form.protein_target_days' => 'dagen eiwitdoel gehaald',
            'form.protein_notes' => 'eiwittoelichting',
            'form.appetite_score' => 'eetlust',
            'form.missed_target_reason' => 'reden',
            'form.missed_target_reason_other' => 'andere reden',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function missedTargetReasons(): array
    {
        return ['geen tijd', 'vakantie', 'blessure', 'motivatie', 'wist niet wat ik moest doen', 'gym niet beschikbaar', 'anders'];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeForProgram(array $validated, Player $player): array
    {
        if (! $player->isMuscleGain()) {
            $validated['weight_kg'] = null;
            $validated['kcal_avg'] = null;
            $validated['protein_status'] = null;
            $validated['protein_avg_grams'] = null;
            $validated['protein_target_days'] = null;
            $validated['protein_notes'] = null;
            $validated['appetite_score'] = null;
            $validated['used_mijn_eetmeter'] = null;
            $validated['used_yazio'] = null;
        }

        if ($player->isMuscleGain()) {
            $validated['pickup_thursday'] = null;
            $validated['protein_status'] = $this->proteinStatusFromDays($validated['protein_target_days'] ?? null) ?? ($validated['protein_status'] ?? null);
        }

        if (! $player->isConditioning()) {
            $validated['rpe_highest'] = null;
        }

        if (! $this->isUnderTarget($player)) {
            $validated['missed_target_reason'] = null;
            $validated['missed_target_reason_other'] = null;
        }

        if (! $this->hasIncompleteProtein($validated['protein_status'] ?? null)) {
            $validated['protein_avg_grams'] = null;
            $validated['protein_notes'] = null;
        }

        if (($validated['missed_target_reason'] ?? null) !== 'anders') {
            $validated['missed_target_reason_other'] = null;
        }

        return $validated;
    }

    private function isUnderTarget(Player $player): bool
    {
        $settings = $player->settings;

        if ((int) ($this->form['strength_sessions'] ?? 0) < (int) ($settings?->strength_target_per_week ?? 0)) {
            return true;
        }

        if ((int) ($this->form['conditioning_sessions'] ?? 0) < (int) ($settings?->conditioning_target_per_week ?? 0)) {
            return true;
        }

        if ((int) ($this->form['mobility_sessions'] ?? 0) < (int) ($settings?->mobility_target_per_week ?? 0)) {
            return true;
        }

        if (! $player->isMuscleGain()) {
            return false;
        }

        $kcalMinimum = (int) ($settings?->kcal_minimum ?? 0);
        $kcalAverage = $this->form['kcal_avg'] ?? null;

        $proteinTargetDays = $this->form['protein_target_days'] ?? null;

        return ($kcalAverage !== null && (int) $kcalAverage < $kcalMinimum)
            || ($proteinTargetDays !== null && (int) $proteinTargetDays < 6)
            || in_array($this->form['protein_status'] ?? null, ['partial', 'no'], true);
    }

    private function hasIncompleteProtein(?string $proteinStatus = null): bool
    {
        if (func_num_args() === 0 && ($this->form['protein_target_days'] ?? null) !== null) {
            return (int) $this->form['protein_target_days'] < 6;
        }

        $status = func_num_args() > 0 ? $proteinStatus : ($this->form['protein_status'] ?? null);

        return in_array($status, ['partial', 'no'], true);
    }

    private function proteinStatusFromDays(mixed $days): ?string
    {
        if ($days === null || $days === '') {
            return null;
        }

        $days = (int) $days;

        if ($days >= 6) {
            return 'yes';
        }

        return $days >= 3 ? 'partial' : 'no';
    }

    private function stepErrorMessage(int $step): string
    {
        $player = $this->currentPlayer();

        if ($step === 1) {
            return 'Kies eerst hoeveel keer je kracht, conditie en mobiliteit/preventie hebt gedaan.';
        }

        if ($step === 2) {
            return 'Vul je slaap in en kies je energie- en spierpijnscore.';
        }

        if ($step === 3 && $player->isMuscleGain()) {
            return 'Vul je gewicht, kcal, eiwitdagen en eetlust in.';
        }

        if ($step === 3 && $player->isConditioning()) {
            return 'Vul de belasting van je zwaarste sessie in.';
        }

        return 'Vul de ontbrekende velden in om verder te gaan.';
    }

    private function currentPlayer(): Player
    {
        return auth()->user()->player()->with('settings')->firstOrFail();
    }

    private function trainingLoad(?int $minutes, ?int $rpe): ?int
    {
        if ($minutes === null || $rpe === null) {
            return null;
        }

        return $minutes * $rpe;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
