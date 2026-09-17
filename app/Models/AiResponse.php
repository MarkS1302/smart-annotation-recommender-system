<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'user_id',
    'input_source_id',
    'knowledge_base_source_id',
    'request_id',
    'entity',
    'record_id',
    'status',
    'answer',
    'ai_response',
    'error',
])]
class AiResponse extends Model implements AuditableContract
{
    use AuditableTrait;

    public static function isAuditingEnabled(): bool
    {
        return (bool) config('audit.enabled', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inputSource(): BelongsTo
    {
        return $this->belongsTo(AnnotationSource::class, 'input_source_id');
    }

    public function knowledgeBaseSource(): BelongsTo
    {
        return $this->belongsTo(AnnotationSource::class, 'knowledge_base_source_id');
    }

    protected function casts(): array
    {
        return [
            'ai_response' => 'array',
        ];
    }
}
