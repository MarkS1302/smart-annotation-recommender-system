<?php

namespace App\Support\AiRequests;

class MetadataFeatureExtractor
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function extract(array $attributes): array
    {
        $textFields = collect($attributes)
            ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
            ->map(function (string $value): array {
                preg_match_all('/[\p{L}\p{N}]+/u', $value, $matches);

                return [
                    'length' => mb_strlen($value),
                    'word_count' => count($matches[0] ?? []),
                ];
            })
            ->all();

        return [
            'field_count' => count($attributes),
            'non_empty_field_count' => collect($attributes)->filter(fn (mixed $value): bool => filled($value))->count(),
            'field_names' => array_keys($attributes),
            'value_types' => collect($attributes)
                ->map(fn (mixed $value): string => get_debug_type($value))
                ->all(),
            'text_fields' => $textFields,
        ];
    }
}
