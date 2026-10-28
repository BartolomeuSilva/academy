<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonProgressRequest extends FormRequest
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
            'position_seconds' => ['required_without:completed', 'integer', 'min:0', 'max:86400'],
            'completed' => ['required_without:position_seconds', 'boolean'],
        ];
    }
}
