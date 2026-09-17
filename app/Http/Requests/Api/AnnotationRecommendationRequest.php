<?php

namespace App\Http\Requests\Api;

use App\Models\AnnotationSource;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnnotationRecommendationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'entity' => ['required', 'string', 'max:255'],
            'record_id' => ['nullable', 'string', 'max:255'],
            'record' => ['required', 'array'],
            'mode' => ['sometimes', Rule::in(['ai', 'without-ai'])],
            'prompt' => ['nullable', 'string'],
            'knowledge_base_source_id' => ['nullable', 'integer',                Rule::exists(AnnotationSource::class, 'id')->where(fn (Builder $query): Builder => $query
                ->where('type', AnnotationSource::TYPE_SQLITE)
                ->where('active', true)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([            'mode' => $this->input('mode', 'ai'),        ]);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->string('mode')->value() === 'without-ai' && ! $this->filled('knowledge_base_source_id')) {
                $validator->errors()->add(
                    'knowledge_base_source_id',
                    'A knowledge base source is required without AI.',
                );
            }
        }];
    }
}
