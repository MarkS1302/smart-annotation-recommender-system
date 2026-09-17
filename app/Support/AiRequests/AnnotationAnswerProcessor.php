<?php

namespace App\Support\AiRequests;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AnnotationAnswerProcessor
{
    public function normalize(?string $answer): ?string
    {
        if (blank($answer)) {
            return null;
        }

        $decoded = json_decode($answer, true);

        if (! is_array($decoded)) {
            return $answer;
        }

        Arr::set($decoded, 'annotations', $this->filterAnnotations(
            Arr::get($decoded, 'annotations', []),
        ));
        Arr::set($decoded, 'uncertain_annotations', $this->filterAnnotations(
            Arr::get($decoded, 'uncertain_annotations', []),
        ));

        $encoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? $answer : $encoded;
    }

    private function filterAnnotations(mixed $annotations): array
    {
        if (! is_array($annotations)) {
            return [];
        }

        $threshold = (float) config('ai.annotation.confidence_threshold', 0);
        $stopwords = collect(config('ai.annotation.stopwords', []))
            ->map(fn (mixed $word): string => Str::lower(trim((string) $word)))
            ->filter()
            ->all();
        $weights = config('ai.annotation.category_weights', []);

        return collect($annotations)
            ->filter(static fn (mixed $annotation): bool => is_array($annotation))
            ->map(function (array $annotation) use ($weights): array {
                $category = (string) Arr::get($annotation, 'category', 'custom');
                $weight = is_array($weights) ? (float) Arr::get($weights, $category, 1) : 1;
                $confidence = (float) Arr::get($annotation, 'confidence', 0) * $weight;

                return [
                    ...$annotation,
                    'confidence' => max(0, min(1, $confidence)),
                ];
            })
            ->filter(function (array $annotation) use ($threshold, $stopwords): bool {
                $tag = Str::lower((string) Arr::get($annotation, 'tag', ''));

                return (float) Arr::get($annotation, 'confidence', 0) >= $threshold
                    && ! in_array($tag, $stopwords, true);
            })
            ->values()
            ->all();
    }
}
