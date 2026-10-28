<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonAttachmentsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:51200'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files' => 'arquivos',
            'files.*' => 'arquivo',
        ];
    }
}
