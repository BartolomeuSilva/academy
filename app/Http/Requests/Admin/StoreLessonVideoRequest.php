<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255', 'regex:/\.(mp4|m4v|mov|mkv|webm)$/i'],
            'size' => ['required', 'integer', 'min:1', 'max:'.config('academy.video_max_upload_bytes')],
            'content_type' => ['nullable', 'string', 'max:100', 'starts_with:video/'],
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
            'filename.regex' => 'Envie um vídeo MP4, M4V, MOV, MKV ou WEBM.',
            'size.max' => 'O vídeo deve ter no máximo 2 GB.',
            'content_type.starts_with' => 'O arquivo enviado não é um vídeo.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['filename' => 'arquivo', 'size' => 'tamanho', 'content_type' => 'tipo'];
    }
}
