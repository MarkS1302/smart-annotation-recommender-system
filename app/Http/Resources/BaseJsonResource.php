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

        $date = $dateTime instanceof DateTimeInterface
            ? Carbon::instance($dateTime)
            : Carbon::createFromFormat($valueFormat->value, $dateTime, 'UTC');

        return $date->setTimezone((string) config('app.timezone'))->format($outputFormat->value);
    }

    protected function formatDate(
        DateTimeInterface|string|null $date,
        DateFormat $outputFormat = DateFormat::DATE_DB,
        DateFormat $valueFormat = DateFormat::DATE_DB,
    ): ?string {

        if (blank($date)) {
            return null;
        }

        $parsedDate = $date instanceof DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::createFromFormat($valueFormat->value, $date, 'UTC');

        return $parsedDate->setTimezone((string) config('app.timezone'))->format($outputFormat->value);
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
            'created_at' => $this->formatDateTime(
                $this->created_at,
                DateFormat::DATE_TIME_GREEK,
            ),
            'updated_at' => $this->formatDateTime(
                $this->updated_at,
                DateFormat::DATE_TIME_GREEK,
            ),
        ];
    }
}
