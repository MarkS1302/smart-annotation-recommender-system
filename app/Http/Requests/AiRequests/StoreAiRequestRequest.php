<?php

namespace App\Http\Requests\AiRequests;

use App\Models\AnnotationSource;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAiRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entity' => ['nullable', 'string', 'required_without:input_source_id'],
            'input_source_id' => [
                'nullable',
                'integer',
                Rule::exists(AnnotationSource::class, 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('type', AnnotationSource::TYPE_JSON)
                        ->where('active', true)),
            ],
            'record_id' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['ai', 'without-ai'])],
            'prompt' => ['nullable', 'string', 'required_if:mode,ai'],
            'knowledge_base_source_id' => [
                'nullable',
                'integer',
                Rule::exists(AnnotationSource::class, 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('type', AnnotationSource::TYPE_SQLITE)
                        ->where('active', true)),
            ],
            'knowledge_base' => [
                'nullable',
                'file',
                'extensions:sqlite,db',
                'max:2048',
                $this->validSqliteFile(...),
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

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->string('mode')->value() === 'without-ai'
                && ! $this->filled('knowledge_base_source_id')
                && ! $this->hasFile('knowledge_base')) {
                $message = 'Choose a saved SQLite knowledge base or upload one.';
                $validator->errors()->add('knowledge_base_source_id', $message);
                $validator->errors()->add('knowledge_base', $message);
            }

            if ($this->filled('input_source_id') && ! $this->filled('record_id')) {
                $validator->errors()->add('record_id', 'Choose a JSON record.');
            }

            if ($this->filled('knowledge_base_source_id') && $this->hasFile('knowledge_base')) {
                $validator->errors()->add(
                    'knowledge_base_source_id',
                    'Choose a saved knowledge base or upload one, not both.',
                );
            }
        }];
    }

    private function validSqliteFile(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $header = file_get_contents($value->getRealPath(), false, null, 0, 16);

        if ($header !== "SQLite format 3\000") {
            $fail('The :attribute must be a valid SQLite database.');
        }
    }
}
