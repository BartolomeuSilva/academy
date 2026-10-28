<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['present', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * Get the ordered ids sent by the client.
     *
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map(intval(...), array_values($this->validated('ids')));
    }
}
