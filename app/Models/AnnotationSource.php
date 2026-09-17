<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'type',
    'file_path',
    'tag_column',
    'active',
    'settings',
    'created_by',
])]
class AnnotationSource extends Model
{
    public const string TYPE_JSON = 'json';

    public const string TYPE_SQLITE = 'sqlite';

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function isJson(): bool
    {
        return $this->type === self::TYPE_JSON;
    }

    public function isSqlite(): bool
    {
        return $this->type === self::TYPE_SQLITE;
    }

    /**
     * @param  Builder<AnnotationSource>  $query
     */
    public function scopeSearch(Builder $query, mixed $value): void
    {
        $search = trim((string) $value);

        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('tag_column', 'like', "%{$search}%");
        });
    }
}
