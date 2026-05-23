<?php

namespace App\Http\Requests;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCoach() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', Rule::in([Player::Conditioning, Player::MuscleGain, Player::Maintenance])],
            'age' => ['nullable', 'integer', 'between:12,40'],
            'height_cm' => ['nullable', 'integer', 'between:140,230'],
            'start_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'long_term_target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
