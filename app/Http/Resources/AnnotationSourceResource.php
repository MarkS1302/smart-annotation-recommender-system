<?php

namespace App\Http\Resources;

use App\Models\AnnotationSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** @mixin AnnotationSource */
class AnnotationSourceResource extends BaseJsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'tag_column' => $this->tag_column,
            'active' => $this->active,
            'created_by' => $this->created_by,
            'json_content' => $this->when(
                $this->type === AnnotationSource::TYPE_JSON,
                function (): ?string {
                    if (blank($this->file_path)
                        || ! Storage::disk('local')->exists($this->file_path)) {
                        return null;
                    }

                    return Storage::disk('local')->get($this->file_path);
                },
            ),
            ...$this->timestamps(),
        ];
    }
}
