<?php

namespace App\Http\Requests\Admin;

use App\Models\CommunitySpace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunitySpaceRequest extends FormRequest
{
    /**
     * Admin routes are already guarded by the admin middleware.
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
        /** @var CommunitySpace|null $space */
        $space = $this->route('space');

        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash:ascii', Rule::notIn(['aulas']), Rule::unique(CommunitySpace::class)->ignore($space)],
            'emoji' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:255'],
            'staff_only' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['slug' => 'endereço', 'position' => 'ordem'];
    }
}
