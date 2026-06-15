<?php

namespace App\Livewire\Coach\Players;

use App\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CheckinPreview extends Component
{
    use AuthorizesRequests;

    public Player $player;

    public array $form = [
        'weight_kg' => null,
        'strength_sessions' => null,
        'conditioning_sessions' => null,
        'mobility_sessions' => null,
        'handle_sessions' => null,
        'handle_minutes' => null,
        'handles_worked_on' => null,
        'pickup_monday' => null,
        'pickup_thursday' => null,
        'pickup_sessions' => null,
        'had_full_rest_day' => false,
        'sleep_avg_hours' => null,
        'energy_score' => null,
        'soreness_score' => null,
        'pain' => false,
        'pain_location' => null,
        'pain_notes' => null,
        'rpe_highest' => null,
        'total_training_minutes' => null,
        'conditioning_minutes' => null,
        'defence_sessions' => null,
        'playbook_calls_learned' => null,
        'playbook_focus' => null,
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

    public int $step = 1;

    public ?string $stepError = null;

    public ?string $validationScrollField = null;

    public int $validationScrollTick = 0;

    public function mount(Player $player): void
    {
        $this->authorize('view', $player);

        $this->player = $player->loadMissing('settings');
    }

    public function nextStep(): void
    {
        $this->validateStep($this->step);
        $this->step = min($this->step + 1, $this->maxStep());
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        $targetStep = max(1, min($step, $this->maxStep()));

        if ($targetStep <= $this->step) {
            $this->stepError = null;
            $this->step = $targetStep;

            return;
        }

        for ($stepToValidate = $this->step; $stepToValidate < $targetStep; $stepToValidate++) {
            $this->step = $stepToValidate;
            $this->validateStep($stepToValidate);
        }

        $this->step = $targetStep;
    }

    public function updatedForm(mixed $value, ?string $key = null): void
    {
        if (! is_string($key) || ! array_key_exists($key, $this->form)) {
            return;
        }

        if ($value === '') {
            $this->form[$key] = null;
        }

        $this->resetValidation("form.{$key}");
        $this->stepError = null;
        $this->validationScrollField = null;

        if ($key === 'protein_target_days') {
            $this->form['protein_status'] = $this->proteinStatusFromDays($this->form['protein_target_days']);
        }
    }

    public function maxStep(): int
    {
        return $this->player->program_type === Player::Maintenance ? 3 : 4;
    }

    public function render(): View
    {
        return view('livewire.coach.players.checkin-preview', [
            'maxStep' => $this->maxStep(),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return $this->requiredRules($this->baseRules());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function baseRules(): array
    {
        return [
            'form.weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.strength_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.conditioning_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.mobility_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.handle_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.handle_minutes' => ['nullable', 'integer', 'between:0,600'],
            'form.handles_worked_on' => ['nullable', 'string', 'max:2000'],
            'form.pickup_monday' => ['nullable', 'boolean'],
            'form.pickup_thursday' => ['nullable', 'boolean'],
            'form.pickup_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.had_full_rest_day' => ['nullable', 'boolean'],
            'form.sleep_avg_hours' => ['nullable', 'numeric', 'between:0,12'],
            'form.energy_score' => ['nullable', 'integer', 'between:1,10'],
            'form.soreness_score' => ['nullable', 'integer', 'between:1,10'],
            'form.pain' => ['boolean'],
            'form.pain_location' => ['nullable', 'string', 'max:255'],
            'form.pain_notes' => ['nullable', 'string', 'max:2000'],
            'form.total_training_minutes' => ['nullable', 'integer', 'between:0,2000'],
            'form.conditioning_minutes' => ['nullable', 'integer', 'between:0,600'],
            'form.defence_sessions' => ['nullable', 'integer', 'min:0', 'max:7'],
            'form.playbook_calls_learned' => ['nullable', 'integer', 'min:0', 'max:10'],
            'form.playbook_focus' => ['nullable', 'string', 'max:2000'],
            'form.highest_session_rpe' => ['nullable', 'integer', 'between:1,10'],
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
    private function requiredRules(array $rules): array
    {
        $rules['form.strength_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.conditioning_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.mobility_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
        $rules['form.sleep_avg_hours'] = ['required', 'numeric', 'between:0,12'];
        $rules['form.energy_score'] = ['required', 'integer', 'between:1,10'];
        $rules['form.soreness_score'] = ['required', 'integer', 'between:1,10'];
        $rules['form.pain_location'] = [Rule::requiredIf((bool) ($this->form['pain'] ?? false)), 'nullable', 'string', 'max:255'];
        $rules['form.total_training_minutes'] = [Rule::requiredIf($this->hasConditioningLoad()), 'nullable', 'integer', 'between:0,2000'];
        $rules['form.highest_session_rpe'] = [Rule::requiredIf($this->hasConditioningLoad()), 'nullable', 'integer', 'between:1,10'];
        $rules['form.missed_target_reason'] = [Rule::requiredIf($this->isUnderTarget()), 'nullable', Rule::in($this->missedTargetReasons())];
        $rules['form.missed_target_reason_other'] = [Rule::requiredIf($this->isUnderTarget() && ($this->form['missed_target_reason'] ?? null) === 'anders'), 'nullable', 'string', 'max:255'];

        if ($this->player->isGuardDevelopment()) {
            $rules['form.handle_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
            $rules['form.handle_minutes'] = ['required', 'integer', 'between:0,600'];
            $rules['form.handles_worked_on'] = ['required', 'string', 'max:2000'];
            $rules['form.pickup_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
            $rules['form.conditioning_minutes'] = [Rule::requiredIf($this->hasConditioningLoad()), 'nullable', 'integer', 'between:0,600'];
            $rules['form.defence_sessions'] = ['required', 'integer', 'min:0', 'max:7'];
            $rules['form.playbook_calls_learned'] = ['required', 'integer', 'min:0', 'max:10'];
            $rules['form.playbook_focus'] = ['required', 'string', 'max:2000'];
        }

        if ($this->player->tracksNutrition()) {
            $rules['form.weight_kg'] = ['required', 'numeric', 'between:40,160'];
            $rules['form.kcal_avg'] = ['required', 'integer', 'between:1000,6000'];
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
    private function rulesForStep(int $step): array
    {
        $rules = $this->rules();

        if ($step === 1) {
            return Arr::only($rules, [
                'form.strength_sessions',
                'form.conditioning_sessions',
                'form.mobility_sessions',
                ...($this->player->isGuardDevelopment() ? [
                    'form.handle_sessions',
                    'form.handle_minutes',
                    'form.handles_worked_on',
                    'form.pickup_sessions',
                    'form.conditioning_minutes',
                    'form.defence_sessions',
                    'form.playbook_calls_learned',
                    'form.playbook_focus',
                ] : []),
                'form.pickup_monday',
                'form.pickup_thursday',
                'form.had_full_rest_day',
                ...($this->player->isConditioning() ? [] : [
                    'form.total_training_minutes',
                    'form.highest_session_rpe',
                ]),
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

        if ($step === 3 && $this->player->tracksNutrition()) {
            return Arr::only($rules, [
                'form.weight_kg',
                'form.kcal_avg',
                'form.protein_avg_grams',
                'form.protein_target_days',
                'form.protein_notes',
                'form.appetite_score',
                'form.used_mijn_eetmeter',
                'form.used_yazio',
            ]);
        }

        if ($step === 3 && $this->player->isConditioning()) {
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

    private function validateStep(int $step): void
    {
        try {
            $this->validate($this->rulesForStep($step), $this->messages(), $this->validationAttributes());
        } catch (ValidationException $exception) {
            $this->stepError = $this->stepErrorMessage($step);
            $this->queueValidationScroll($this->firstValidationErrorField($exception));

            throw $exception;
        }

        $this->stepError = null;
        $this->validationScrollField = null;
    }

    private function firstValidationErrorField(ValidationException $exception): ?string
    {
        $field = array_key_first($exception->errors());

        if (! is_string($field)) {
            return null;
        }

        return str_starts_with($field, 'form.') ? substr($field, 5) : $field;
    }

    private function queueValidationScroll(?string $field): void
    {
        if ($field === null) {
            return;
        }

        $this->validationScrollField = $field;
        $this->validationScrollTick++;
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'form.strength_sessions.required' => 'Kies hoeveel krachttrainingen je hebt gedaan.',
            'form.conditioning_sessions.required' => 'Kies hoeveel conditietrainingen je hebt gedaan.',
            'form.mobility_sessions.required' => 'Kies hoeveel preventie/mobiliteit je hebt gedaan.',
            'form.handle_sessions.required' => 'Kies hoeveel handle/passing sessies je hebt gedaan.',
            'form.handle_minutes.required' => 'Vul je totale handle-minuten in.',
            'form.handles_worked_on.required' => 'Vul in welke handles of passing-drills je hebt geoefend.',
            'form.pickup_sessions.required' => 'Kies hoeveel pickups je hebt gespeeld.',
            'form.conditioning_minutes.required' => 'Vul je conditieminuten in.',
            'form.defence_sessions.required' => 'Kies hoeveel defence/first-step blokken je hebt gedaan.',
            'form.playbook_calls_learned.required' => 'Kies hoeveel calls of acties je hebt geleerd of herhaald.',
            'form.playbook_focus.required' => 'Vul in welke call/play je hebt geleerd of herhaald.',
            'form.sleep_avg_hours.required' => 'Vul je gemiddelde slaap per nacht in.',
            'form.energy_score.required' => 'Kies je energiescore.',
            'form.soreness_score.required' => 'Kies of je spierpijn licht of zwaar voelde.',
            'form.total_training_minutes.required' => 'Vul je totale trainingsminuten in.',
            'form.missed_target_reason.required' => 'Kies waarom het niet gelukt is.',
            'form.missed_target_reason_other.required' => 'Vul kort in wat de andere reden is.',
            'form.pain_location.required' => 'Vul in waar de pijn zit.',
            'form.weight_kg.required' => 'Vul je gewicht in.',
            'form.kcal_avg.required' => 'Vul je gemiddelde kcal per dag in.',
            'form.protein_avg_grams.required' => 'Vul in hoeveel gram eiwit je gemiddeld haalde.',
            'form.protein_target_days.required' => 'Kies hoeveel dagen je het eiwitdoel haalde.',
            'form.protein_notes.required' => 'Vul kort in wat er wel of niet lukte met eiwit.',
            'form.appetite_score.required' => 'Kies je eetlustscore.',
            'form.highest_session_rpe.required' => 'Kies de zwaarte van je zwaarste sessie.',
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
            'form.handle_sessions' => 'handle/passing sessies',
            'form.handle_minutes' => 'handle-minuten',
            'form.handles_worked_on' => 'geoefende handles',
            'form.pickup_sessions' => 'pickups',
            'form.conditioning_minutes' => 'conditieminuten',
            'form.defence_sessions' => 'defence/first-step blokken',
            'form.playbook_calls_learned' => 'calls geleerd',
            'form.playbook_focus' => 'playbook focus',
            'form.sleep_avg_hours' => 'slaap',
            'form.energy_score' => 'energie',
            'form.soreness_score' => 'spierpijn',
            'form.total_training_minutes' => 'trainingsminuten',
            'form.highest_session_rpe' => 'zwaarste sessie',
            'form.weight_kg' => 'gewicht',
            'form.kcal_avg' => 'kcal',
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

    private function isUnderTarget(): bool
    {
        $settings = $this->player->settings;

        if ((int) ($this->form['strength_sessions'] ?? 0) < (int) ($settings?->strength_target_per_week ?? 0)) {
            return true;
        }

        if ((int) ($this->form['conditioning_sessions'] ?? 0) < (int) ($settings?->conditioning_target_per_week ?? 0)) {
            return true;
        }

        if ((int) ($this->form['mobility_sessions'] ?? 0) < (int) ($settings?->mobility_target_per_week ?? 0)) {
            return true;
        }

        if ($this->player->isGuardDevelopment()) {
            if ((int) ($this->form['handle_sessions'] ?? 0) < (int) ($settings?->handle_sessions_target_per_week ?? 0)) {
                return true;
            }

            if ((int) ($this->form['handle_minutes'] ?? 0) < (int) ($settings?->handle_minutes_target_per_week ?? 0)) {
                return true;
            }

            if ((int) ($this->form['pickup_sessions'] ?? 0) < (int) ($settings?->pickup_target_per_week ?? 0)) {
                return true;
            }

            if ((int) ($this->form['conditioning_minutes'] ?? 0) < (int) ($settings?->conditioning_minutes_target_per_week ?? 0)) {
                return true;
            }

            if ((int) ($this->form['defence_sessions'] ?? 0) < (int) ($settings?->defence_sessions_target_per_week ?? 0)) {
                return true;
            }

            if ((int) ($this->form['playbook_calls_learned'] ?? 0) < (int) ($settings?->playbook_calls_target_per_week ?? 0)) {
                return true;
            }
        }

        if (! $this->player->tracksNutrition()) {
            return false;
        }

        $kcalMinimum = (int) ($settings?->kcal_minimum ?? 0);
        $kcalAverage = $this->form['kcal_avg'] ?? null;
        $proteinTargetDays = $this->form['protein_target_days'] ?? null;

        return ($kcalAverage !== null && (int) $kcalAverage < $kcalMinimum)
            || ($proteinTargetDays !== null && (int) $proteinTargetDays < 6)
            || in_array($this->form['protein_status'] ?? null, ['partial', 'no'], true);
    }

    private function hasIncompleteProtein(): bool
    {
        $proteinTargetDays = $this->form['protein_target_days'] ?? null;

        if ($proteinTargetDays !== null) {
            return (int) $proteinTargetDays < 6;
        }

        return in_array($this->form['protein_status'] ?? null, ['partial', 'no'], true);
    }

    private function hasConditioningLoad(): bool
    {
        return (int) ($this->form['conditioning_sessions'] ?? 0) > 0
            || (int) ($this->form['conditioning_minutes'] ?? 0) > 0
            || (bool) ($this->form['pickup_monday'] ?? false)
            || (bool) ($this->form['pickup_thursday'] ?? false);
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
        if ($step === 1) {
            if ($this->hasConditioningLoad() && (($this->form['total_training_minutes'] ?? null) === null || ($this->form['highest_session_rpe'] ?? null) === null)) {
                return 'Vul ook de minuten en zwaarste RPE van je conditie/pickup in.';
            }

            return 'Kies eerst hoeveel keer je kracht, conditie en mobiliteit/preventie hebt gedaan.';
        }

        if ($step === 2) {
            return 'Vul je slaap in en kies je energie- en spierpijnscore.';
        }

        if ($step === 3 && $this->player->tracksNutrition()) {
            return 'Vul je gewicht, kcal, eiwitdagen en eetlust in.';
        }

        if ($step === 3 && $this->player->isConditioning()) {
            return 'Vul de belasting van je zwaarste sessie in.';
        }

        return 'Vul de ontbrekende velden in om verder te gaan.';
    }
}
