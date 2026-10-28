<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Models\Lesson;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'status' => ['sometimes', 'required', Rule::enum(PublicationStatus::class)],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Lesson $lesson */
                $lesson = $this->route('lesson');

                if ($this->input('status') === PublicationStatus::Published->value && ! $lesson->hasPlayableVideo()) {
                    $validator->errors()->add('status', 'A aula só pode ser publicada depois que o vídeo estiver pronto.');
                }
            },
        ];
    }
}
