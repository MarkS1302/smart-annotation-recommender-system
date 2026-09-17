<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Models\Audit as BaseAudit;

class Audit extends BaseAudit
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        $morphPrefix = (string) config('audit.user.morph_prefix', 'user');

        return $this->morphTo(
            __FUNCTION__,
            sprintf('%s_type', $morphPrefix),
            sprintf('%s_id', $morphPrefix),
        );
    }

    /**
     * @param  Builder<Audit>  $query
     */
    public function scopeSearch(Builder $query, mixed $value): void
    {
        $search = trim((string) $value);

        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('auditable_type', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhere('user_agent', 'like', "%{$search}%")
                ->orWhereHas('user', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }
}
