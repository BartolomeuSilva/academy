<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderLessonsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'modules' => ['present', 'array'],
            'modules.*.id' => ['required', 'integer', 'distinct'],
            'modules.*.lesson_ids' => ['present', 'array'],
            'modules.*.lesson_ids.*' => ['integer', 'distinct:strict'],
        ];
    }

    /**
     * Get the lesson order grouped by module id.
     *
     * @return array<int, list<int>>
     */
    public function lessonIdsByModule(): array
    {
        /** @var list<array{id: int|string, lesson_ids: list<int|string>}> $modules */
        $modules = $this->validated('modules');

        return collect($modules)
            ->mapWithKeys(fn (array $module) => [(int) $module['id'] => array_map(intval(...), $module['lesson_ids'])])
            ->all();
    }
}
