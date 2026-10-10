<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

final class LocalBookingTime
{
    /** Resolve a wall time without allowing PHP to silently normalize DST gaps or folds. */
    public function parse(string $value, string $timezone): CarbonImmutable
    {
        $value = preg_replace('/\.0{1,6}(?=Z|[+-]|$)/', '', $value);
        if (preg_match('/(?:Z|[+-]\d{2}:\d{2})$/', $value)) {
            return CarbonImmutable::parse($value)->utc();
        }

        $wallTime = str_replace('T', ' ', $value);
        if (strlen($wallTime) === 16) {
            $wallTime .= ':00';
        }

        $wall = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $wallTime, 'UTC');
        if ($wall === false || $wall->format('Y-m-d H:i:s') !== $wallTime) {
            throw ValidationException::withMessages(['scheduled_at' => 'موعد الحجز غير صالح.']);
        }

        $zone = new DateTimeZone($timezone);
        $transitions = $zone->getTransitions($wall->timestamp - 172800, $wall->timestamp + 172800);
        $offsets = array_unique(array_column($transitions ?: [['offset' => $zone->getOffset($wall)]], 'offset'));
        $matches = [];
        foreach ($offsets as $offset) {
            $candidate = $wall->subSeconds($offset);
            if ($candidate->setTimezone($timezone)->format('Y-m-d H:i:s') === $wallTime) {
                $matches[] = $candidate;
            }
        }

        if (count($matches) !== 1) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'هذا التوقيت المحلي غير موجود أو متكرر بسبب التوقيت الصيفي. أرسل موعدًا بإزاحة زمنية صريحة.',
            ]);
        }

        return $matches[0];
    }
}
