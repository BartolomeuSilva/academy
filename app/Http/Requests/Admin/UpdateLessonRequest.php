<?php

namespace App\Http\Requests\Admin;

use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Lesson $lesson */
        $lesson = $this->route('lesson');

        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'required', 'string', 'max:160', 'alpha_dash:ascii', 'lowercase',
                Rule::unique('lessons', 'slug')->where('course_id', $lesson->course_id)->ignore($lesson),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
