<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

/** @mixin Audit */
class AuditResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'old_values' => $this->old_values ?? [],
            'new_values' => $this->new_values ?? [],
            'user' => $this->whenLoaded('user', function (): ?array {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            ...$this->timestamps(),
        ];
    }
}
