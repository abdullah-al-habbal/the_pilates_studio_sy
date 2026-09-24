<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\WeekdayEnum;
use Illuminate\Validation\ValidationException;

final readonly class ClassInputNormalizer
{
    private const MODE_WEEKDAYS = 'weekdays';

    /**
     * Normalize class input without assuming whether it came from Filament or Operations.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        $data = $this->normalizeSchedule($data);

        foreach (['start_time', 'end_time'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = $this->normalizeTime($field, (string) $data[$field]);
            }
        }

        foreach (['title', 'about'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->normalizeTranslatedText($data[$field], $field === 'about');
            }
        }

        if (array_key_exists('total_spots', $data) && $data['total_spots'] !== null) {
            $data['total_spots'] = (int) $data['total_spots'];
        }

        foreach (['instructor_id', 'class_category_id', 'recurrence_pattern_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === null || $data[$field] === ''
                    ? null
                    : (int) $data[$field];
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public function normalizeSchedule(array $data): array
    {
        $mode = $data['schedule_mode'] ?? null;
        unset($data['schedule_mode']);

        if ($mode !== null && $mode !== self::MODE_WEEKDAYS) {
            $data['weekdays'] = null;

            return $data;
        }

        if ($mode === null && ! array_key_exists('weekdays', $data)) {
            return $data;
        }

        $weekdays = $data['weekdays'] ?? null;

        if (is_array($weekdays) && $weekdays !== []) {
            $data['weekdays'] = $this->normalizeWeekdays($weekdays);
            $data['recurrence_pattern_id'] = null;

            return $data;
        }

        $data['weekdays'] = null;

        return $data;
    }

    private function normalizeTime(string $field, string $value): string
    {
        $value = trim($value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $second = isset($matches[3]) ? (int) $matches[3] : 0;

            if ($hour <= 23 && $minute <= 59 && $second <= 59) {
                return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
            }
        }

        if (preg_match('/^(1[0-2]|0?[1-9]):([0-5]\d)\s*(AM|PM)$/i', $value, $matches) === 1) {
            $hour = (int) $matches[1] % 12;
            if (strtoupper($matches[3]) === 'PM') {
                $hour += 12;
            }

            return sprintf('%02d:%02d:00', $hour, (int) $matches[2]);
        }

        throw ValidationException::withMessages([
            $field => __('dashboard.resources.classes.validation.invalid_time_format'),
        ]);
    }

    /**
     * @param  array<mixed>  $weekdays
     *
     * @return list<string>
     */
    private function normalizeWeekdays(array $weekdays): array
    {
        $normalized = [];

        foreach ($weekdays as $weekday) {
            $value = is_string($weekday) ? strtolower(trim($weekday)) : null;

            if ($value === null || WeekdayEnum::tryFrom($value) === null) {
                throw ValidationException::withMessages([
                    'weekdays' => __('dashboard.resources.classes.validation.invalid_weekday'),
                ]);
            }

            if (! in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    private function normalizeTranslatedText(mixed $value, bool $html): mixed
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($translation) => $this->normalizeTextValue($translation, $html))
                ->all();
        }

        return $this->normalizeTextValue($value, $html);
    }

    private function normalizeTextValue(mixed $value, bool $html): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '' || ($html && blank(strip_tags($text)))) {
            return null;
        }

        return $text;
    }
}
