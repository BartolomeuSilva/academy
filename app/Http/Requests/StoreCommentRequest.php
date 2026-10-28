<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
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
        /** @var Lesson $lesson */
        $lesson = $this->route('lesson');

        return [
            'body' => ['required', 'string', 'min:1', 'max:'.Comment::MAX_LENGTH],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')
                    ->where('lesson_id', $lesson->id)
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
            ],
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
            'parent_id.exists' => 'Só é possível responder a um comentário principal desta aula.',
        ];
    }
}
