<?php

namespace App\Http\Requests\AnnotationSources;

use App\Models\AnnotationSource;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class UpdateAnnotationSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update.annotation-sources') ?? false;
    }

    public function rules(): array
    {
        $source = $this->route('annotation_source');
        $type = $source instanceof AnnotationSource ? $source->type : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'file' => [
                'nullable',
                'file',
                'extensions:json,sqlite,db',
                'max:10240',
                $this->validSourceFile(...),
            ],
            'tag_column' => [
                'nullable',
                'string',
                'max:255',
                $type === AnnotationSource::TYPE_SQLITE ? 'required' : 'nullable',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
            'active' => ['required', 'boolean'],
        ];
    }

    private function validSourceFile(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $source = $this->route('annotation_source');

        if ($source instanceof AnnotationSource && $source->isSqlite()) {
            $header = file_get_contents($value->getRealPath(), false, null, 0, 16);

            if ($header !== "SQLite format 3\000") {
                $fail('The :attribute must be a valid SQLite database.');
            }

            return;
        }

        $payload = json_decode((string) file_get_contents($value->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
            $fail('The :attribute must contain valid JSON.');

            return;
        }

        $records = Arr::get($payload, 'records');
        $record = Arr::get($payload, 'record');

        if (is_array($record)) {
            $records = [$record];
        }

        $entity = Arr::get($payload, 'entity');

        if (! is_string($entity)
            || blank($entity)
            || ! is_array($records)
            || blank($records)
            || collect($records)->contains(fn (mixed $record): bool => ! is_array($record))) {
            $fail('JSON source must contain an entity and a record or records array.');
        }
    }
}
