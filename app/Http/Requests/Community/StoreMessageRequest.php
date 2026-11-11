<?php

namespace App\Http\Requests\Community;

use App\Models\CommunityImage;
use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Team-only channels are checked by the controller.
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
        /** @var CommunitySpace $space */
        $space = $this->route('space');

        return [
            'body' => ['required_without:images', 'nullable', 'string', 'max:'.CommunityMessage::MAX_BODY_LENGTH],
            'reply_to_id' => [
                'nullable',
                'integer',
                Rule::exists('community_messages', 'id')->where('space_id', $space->id)->whereNull('deleted_at'),
            ],
            'images' => ['nullable', 'array', 'max:'.CommunityImage::MAX_PER_POST],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.CommunityImage::MAX_KILOBYTES],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['body.required_without' => 'Escreva uma mensagem ou envie uma imagem.'];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'mensagem', 'reply_to_id' => 'mensagem respondida', 'images' => 'imagens', 'images.*' => 'imagem'];
    }
}
