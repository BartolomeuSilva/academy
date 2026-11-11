<?php

namespace App\Http\Requests;

use App\Models\LessonRating;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRatingRequest extends FormRequest
{
    /**
     * Visibility of the lesson is checked by the controller, which answers 404 instead of 403.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stars' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'string', 'max:'.LessonRating::MAX_FEEDBACK_LENGTH],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stars.required' => 'Escolha de 1 a 5 estrelas.',
            'stars.between' => 'Escolha de 1 a 5 estrelas.',
            'feedback.max' => 'O comentário pode ter até :max caracteres.',
        ];
    }
}
