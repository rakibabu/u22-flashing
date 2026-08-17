<?php

namespace App\Http\Requests;

use App\Models\ExerciseLibraryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class ImportExerciseLibraryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExerciseLibraryItem::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archive' => ['required', 'file', 'extensions:zip', 'max:102400'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'archive.required' => 'Kies een ZIP-bestand om te importeren.',
            'archive.extensions' => 'Kies een geldig ZIP-bestand.',
            'archive.max' => 'Het ZIP-bestand mag maximaal 100 MB groot zijn.',
        ];
    }

    /**
     * Redirect upload validation errors back to the Livewire page as a visible flash message.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            to_route('coach.exercises.index')->with('exercise-import-error', $validator->errors()->first('archive')),
        );
    }
}
