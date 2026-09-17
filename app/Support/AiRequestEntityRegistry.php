<?php

namespace App\Support;

use App\Models\AiResponse;
use App\Models\AnnotationSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class AiRequestEntityRegistry
{
    private ?array $entityDefinitions = null;

    public function entities(): array
    {
        return $this->entityDefinitions ??= $this->modelClasses()
            ->map(fn (string $modelClass): array => $this->entityDefinition($modelClass))
            ->sortBy('label')
            ->values()
            ->all();
    }

    public function keys(): array
    {
        return array_map(
            static fn (array $entity): string => (string) Arr::get($entity, 'value'),
            $this->entities(),
        );
    }

    public function find(string $entity): ?array
    {
        return collect($this->entities())->first(fn (array $candidate): bool => Arr::get($candidate, 'value') === $entity);
    }

    public function recordExists(string $entity, mixed $recordId): bool
    {
        return $this->findRecord($entity, $recordId) !== null;
    }

    public function findRecord(string $entity, mixed $recordId): ?Model
    {
        $entityDefinition = $this->find($entity);

        if (blank($entityDefinition)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = Arr::get($entityDefinition, 'modelClass');

        /** @var Model|null $record */
        $record = $modelClass::query()->find($recordId);

        return $record;
    }

    private function modelClasses(): Collection
    {
        return collect(File::allFiles(app_path('Models')))
            ->map(fn (SplFileInfo $file): string => $this->classFromFile($file))
            ->filter(
                static fn (string $modelClass): bool => class_exists($modelClass)
                    && is_subclass_of($modelClass, Model::class)
                    && ! in_array($modelClass, [AiResponse::class, AnnotationSource::class], true),
            )
            ->values();
    }

    private function entityDefinition(string $modelClass): array
    {
        /** @var Model $model */
        $model = new $modelClass;

        return [
            'value' => Str::kebab(class_basename($modelClass)),
            'label' => Str::headline(class_basename($modelClass)),
            'modelClass' => $modelClass,
            'table' => $model->getTable(),
            'primaryKey' => $model->getKeyName(),
            'records' => $this->recordsFor($modelClass, $model),
        ];
    }

    private function recordsFor(string $modelClass, Model $model): array
    {
        return $modelClass::query()
            ->orderBy($model->getKeyName())
            ->get()
            ->map(fn (Model $record): array => [
                'value' => (string) $record->getKey(),
                'label' => $this->recordLabel($record),
            ])
            ->all();
    }

    private function classFromFile(SplFileInfo $file): string
    {
        $relativePath = Str::after($file->getRealPath(), app_path('Models').DIRECTORY_SEPARATOR);

        return 'App\\Models\\'.str_replace(
            [DIRECTORY_SEPARATOR, '/'],
            '\\',
            Str::beforeLast($relativePath, '.php'),
        );
    }

    private function recordLabel(Model $record): string
    {
        $attributes = $record->attributesToArray();
        $summaryColumn = collect(['name', 'title', 'email'])
            ->first(fn (string $column): bool => Arr::has($attributes, $column));

        if (filled($summaryColumn)) {
            return sprintf(
                '%s (#%s)',
                Str::limit((string) Arr::get($attributes, $summaryColumn), 60),
                $record->getKey(),
            );
        }

        return sprintf('Record #%s', $record->getKey());
    }
}
