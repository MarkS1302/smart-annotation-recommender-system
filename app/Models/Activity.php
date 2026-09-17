<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    /**
     * @param  Builder<Activity>  $query
     */
    public function scopeSearch(Builder $query, mixed $value): void
    {
        $search = trim((string) $value);

        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('log_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%")
                ->orWhere('subject_type', 'like', "%{$search}%")
                ->orWhere('causer_type', 'like', "%{$search}%");
        });
    }
}
