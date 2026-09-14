<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/** @mixin Activity */
class ActivityLogResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer' => $this->whenLoaded('causer', function (): ?array {
                return [
                    'id' => $this->causer->id,
                    'name' => $this->causer->name,
                    'email' => $this->causer->email,
                ];
            }),
            'event' => $this->event,
            'properties' => $this->properties?->toArray() ?? [],
            ...$this->timestamps(),
        ];
    }
}
