<?php

namespace App\Http\Requests\AiRequests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entity' => ['required', 'string'],
            'mode' => ['required', Rule::in(['ai', 'without-ai'])],
            'prompt' => ['nullable', 'string', 'required_if:mode,ai'],
            'knowledge_base' => [
                'nullable',
                'required_if:mode,without-ai',
                'file',
                'extensions:sqlite,db',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! $value->isValid()) {
                        return;
                    }

                    $header = file_get_contents($value->getRealPath(), false, null, 0, 16);

                    if ($header !== "SQLite format 3\000") {
                        $fail('The :attribute must be a valid SQLite database.');
                    }
                },
            ],
            'tag_column' => [
                'nullable',
                'string',
                'max:255',
                'required_with:knowledge_base',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
        ];
    }
}
