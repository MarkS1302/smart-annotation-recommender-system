<?php

namespace App\Support\AiRequests;

use App\Models\AnnotationSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AnnotationSourceManager
{
    public function options(string $type): array
    {
        return AnnotationSource::query()
            ->where('type', $type)
            ->where('active', true)
            ->latest('name')
            ->get()
            ->map(fn (AnnotationSource $source): array => [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type,
                'tag_column' => $source->tag_column,
                'records' => $source->isJson() ? $this->recordOptions($source) : [],
            ])
            ->values()
            ->all();
    }

    public function recordOptions(AnnotationSource $source): array
    {
        return collect($this->jsonRecords($source))
            ->map(fn (AnnotationRecord $record): array => [
                'value' => $record->recordId,
                'label' => $this->recordLabel($record),
            ])
            ->all();
    }

    public function record(AnnotationSource $source, string $recordId)
    {
        $record = collect($this->jsonRecords($source))
            ->first(fn (AnnotationRecord $candidate): bool => $candidate->recordId === $recordId);

        if (blank($record)) {
            throw new RuntimeException('The selected JSON record does not exist.');
        }

        return $record;
    }

    public function jsonRecords(AnnotationSource $source): array
    {
        if (! $source->isJson() || blank($source->file_path)) {
            throw new RuntimeException('The selected source is not a JSON record source.');
        }

        $payload = json_decode(
            Storage::disk('local')->get($source->file_path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $entity = (string) Arr::get($payload, 'entity');
        $records = Arr::get($payload, 'records');
        $record = Arr::get($payload, 'record');

        if (is_array($record)) {
            $records = [$record];
        }

        if (blank($entity) || ! is_array($records) || blank($records)) {
            throw new RuntimeException('JSON source must contain an entity and a record or records array.');
        }

        return collect($records)
            ->filter(static fn (mixed $record): bool => is_array($record))
            ->values()
            ->map(fn (array $record, int $index): AnnotationRecord => AnnotationRecord::fromJson(
                entity: $entity,
                record: $record,
                recordId: (string) Arr::get($record, 'id', $index + 1),
            ))
            ->all();
    }

    private function recordLabel(AnnotationRecord $record): string
    {
        $summaryColumn = collect(['name', 'title', 'email'])
            ->first(fn (string $column): bool => Arr::has($record->attributes, $column));

        if (filled($summaryColumn)) {
            return sprintf(
                '%s (#%s)',
                Str::limit((string) Arr::get($record->attributes, $summaryColumn), 60),
                $record->recordId,
            );
        }

        return sprintf('Record #%s', $record->recordId);
    }
}
