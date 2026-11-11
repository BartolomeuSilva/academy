<?php

namespace App\Http\Requests\Community;

use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    /**
     * Team-only spaces are checked by the controller once the space is known to exist.
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
            'space_id' => ['required', 'integer', 'exists:'.CommunitySpace::class.',id'],
            'title' => ['required', 'string', 'min:3', 'max:'.CommunityTopic::MAX_TITLE_LENGTH],
            'body' => ['required', 'string', 'max:'.CommunityTopic::MAX_BODY_LENGTH],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['space_id' => 'espaço', 'body' => 'mensagem'];
    }
}
