<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DateFormat;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE;
    }

    protected function formatDateTime(
        DateTimeInterface|string|null $dateTime,
        DateFormat $outputFormat = DateFormat::DATE_TIME_DB,
        DateFormat $valueFormat = DateFormat::DATE_TIME_DB,
    ): ?string {

        if (blank($dateTime)) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format($outputFormat->value);
        }

        return Carbon::createFromFormat($valueFormat->value, $dateTime)->format($outputFormat->value);
    }

    protected function formatDate(
        DateTimeInterface|string|null $date,
        DateFormat $outputFormat = DateFormat::DATE_DB,
        DateFormat $valueFormat = DateFormat::DATE_DB,
    ): ?string {

        if (blank($date)) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format($outputFormat->value);
        }

        return Carbon::createFromFormat($valueFormat->value, $date)->format($outputFormat->value);
    }

    protected function formatBoolean(int|bool|null $value): ?bool
    {
        if (blank($value)) {
            return null;
        }

        return (bool) $value;
    }

    protected function timestamps(): array
    {
        return [
            'created_at' => $this->created_at?->format(
                DateFormat::DATE_TIME_GREEK->value,
            ),
            'updated_at' => $this->updated_at?->format(
                DateFormat::DATE_TIME_GREEK->value,
            ),
        ];
    }
}
