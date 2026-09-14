<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'user_id',
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

    /**
     * Keep AI request lifecycle changes auditable when queue workers run in console mode.
     */
    public static function isAuditingEnabled(): bool
    {
        return (bool) config('audit.enabled', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ai_response' => 'array',
        ];
    }
}
