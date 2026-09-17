<?php

namespace App\Support\AiRequests;

use Illuminate\Support\Str;

final class AnnotationRecord
{
    public function __construct(
        public string $entity,
        public string $entityLabel,
        public string $recordId,
        public array $attributes,
        public ?string $modelClass = null,
        public ?string $modelDefinition = null,
        public ?array $databaseSchema = null,
    ) {}

    public static function fromJson(
        string $entity,
        array $record,
        string $recordId,
        ?string $entityLabel = null,
    ): self {
        return new self(
            entity: Str::kebab($entity),
            entityLabel: $entityLabel ?? Str::headline($entity),
            recordId: $recordId,
            attributes: $record,
        );
    }

    public function modelName(): string
    {
        return $this->modelClass === null
            ? $this->entityLabel
            : class_basename($this->modelClass);
    }
}
