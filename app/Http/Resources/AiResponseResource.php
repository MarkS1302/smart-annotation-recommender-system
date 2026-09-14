<?php

namespace App\Http\Resources;

use App\Models\AiResponse;
use Illuminate\Http\Request;

/** @mixin AiResponse */
class AiResponseResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'entity' => $this->entity,
            'record_id' => $this->record_id,
            'status' => $this->status,
            'answer' => $this->answer,
            'ai_response' => $this->ai_response,
            'error' => $this->error,
            ...$this->timestamps(),
        ];
    }
}
