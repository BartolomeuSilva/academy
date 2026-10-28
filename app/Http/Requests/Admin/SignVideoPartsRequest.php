<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SignVideoPartsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'part_numbers' => ['required', 'array', 'min:1', 'max:20'],
            'part_numbers.*' => ['integer', 'min:1', 'max:10000', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function partNumbers(): array
    {
        return array_map(intval(...), array_values($this->validated('part_numbers')));
    }
}
