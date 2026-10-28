<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteVideoUploadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parts' => ['required', 'array', 'min:1', 'max:10000'],
            'parts.*.part_number' => ['required', 'integer', 'min:1', 'distinct'],
            'parts.*.etag' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * The parts in the shape S3 expects, sorted by part number.
     *
     * @return list<array{PartNumber: int, ETag: string}>
     */
    public function s3Parts(): array
    {
        /** @var list<array{part_number: int|string, etag: string}> $parts */
        $parts = $this->validated('parts');

        return array_values(collect($parts)
            ->map(fn (array $part) => ['PartNumber' => (int) $part['part_number'], 'ETag' => $part['etag']])
            ->sortBy('PartNumber')
            ->all());
    }
}
