<?php

namespace App\Actions\ServiceCenters;

use App\Models\ServiceCenter;
use App\Support\LocalBookingTime;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class ResolveScheduleAction
{
    public function __construct(private readonly LocalBookingTime $times) {}

    /** @return array{date: string, timezone: string, source: string, is_closed: bool, opens_at: ?string, closes_at: ?string, is_available: bool, opens_at_utc: ?string, closes_at_utc: ?string} */
    public function handle(ServiceCenter $center, string $date): array
    {
        $schedule = $center->scheduleExceptions()->where('date', $date)->first();
        $source = 'exception';
        if ($schedule === null) {
            $schedule = $center->openingHours()
                ->where('day_of_week', strtolower(CarbonImmutable::parse($date, 'UTC')->format('l')))
                ->first();
            $source = $schedule === null ? 'unconfigured' : 'weekly';
        }

        $opens = null;
        $closes = null;
        $available = $schedule !== null && ! $schedule->is_closed && $schedule->opens_at !== null && $schedule->closes_at !== null;
        if ($available) {
            try {
                $opens = $this->times->parse($date.' '.$schedule->opens_at, $center->timezone);
                $closes = $this->times->parse($date.' '.$schedule->closes_at, $center->timezone);
                $available = $opens->lessThan($closes);
            } catch (ValidationException) {
                $available = false;
            }
        }

        return [
            'is_available' => $available,
            'opens_at_utc' => $available ? $opens->toISOString() : null,
            'closes_at_utc' => $available ? $closes->toISOString() : null,
            'date' => $date,
            'timezone' => $center->timezone,
            'source' => $source,
            'is_closed' => $schedule === null || $schedule->is_closed,
            'opens_at' => $schedule?->opens_at === null ? null : substr($schedule->opens_at, 0, 5),
            'closes_at' => $schedule?->closes_at === null ? null : substr($schedule->closes_at, 0, 5),
        ];
    }

    public function allows(ServiceCenter $center, CarbonImmutable $start, int $durationMinutes): bool
    {
        $local = $start->setTimezone($center->timezone);
        $schedule = $this->handle($center, $local->toDateString());
        if (! $schedule['is_available'] || $schedule['is_closed'] || $schedule['opens_at'] === null || $schedule['closes_at'] === null || $durationMinutes < 1) {
            return false;
        }

        $opens = CarbonImmutable::parse($schedule['opens_at_utc']);
        $closes = CarbonImmutable::parse($schedule['closes_at_utc']);

        $end = $start->utc()->addMinutes($durationMinutes);

        return $opens->lessThan($closes)
            && $start->greaterThanOrEqualTo($opens)
            && $end->lessThanOrEqualTo($closes)
            && $end->setTimezone($center->timezone)->toDateString() === $local->toDateString();
    }

    public function validateBooking(ServiceCenter $center, CarbonImmutable $start, int $durationMinutes): void
    {
        if ($this->allows($center, $start, $durationMinutes)) {
            return;
        }

        $schedule = $this->handle($center, $start->setTimezone($center->timezone)->toDateString());
        $message = match (true) {
            $schedule['source'] === 'unconfigured' => 'مواعيد العمل غير محددة لهذا اليوم. اختر يومًا آخر.',
            $schedule['is_closed'] => 'المركز مغلق في اليوم المختار. اختر يومًا آخر.',
            default => 'يجب أن يبدأ الحجز وينتهي بالكامل خلال مواعيد العمل بتوقيت '.$center->timezone.'.',
        };
        throw ValidationException::withMessages(['scheduled_at' => $message]);
    }
}
