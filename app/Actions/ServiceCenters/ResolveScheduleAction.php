<?php

namespace App\Actions\ServiceCenters;

use App\Models\ServiceCenter;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class ResolveScheduleAction
{
    /** @return array{date: string, source: string, is_closed: bool, opens_at: ?string, closes_at: ?string, is_available: bool} */
    public function handle(ServiceCenter $center, string $date): array
    {
        $schedule = $center->scheduleExceptions()->where('date', $date)->first();
        $source = 'exception';
        if ($schedule === null) {
            $schedule = $center->openingHours()
                ->where('day_of_week', strtolower(CarbonImmutable::parse($date)->format('l')))
                ->first();
            $source = $schedule === null ? 'unconfigured' : 'weekly';
        }

        return [
            'date' => $date,
            'source' => $source,
            'is_closed' => $schedule === null || $schedule->is_closed,
            'opens_at' => $schedule?->opens_at === null ? null : substr($schedule->opens_at, 0, 5),
            'closes_at' => $schedule?->closes_at === null ? null : substr($schedule->closes_at, 0, 5),
            'is_available' => $schedule !== null && ! $schedule->is_closed && $schedule->opens_at !== null && $schedule->closes_at !== null,
        ];
    }

    public function allows(ServiceCenter $center, CarbonImmutable $start, int $durationMinutes): bool
    {
        $schedule = $this->handle($center, $start->toDateString());
        if (! $schedule['is_available'] || $durationMinutes < 1) {
            return false;
        }

        $end = $start->addMinutes($durationMinutes);

        return $start->format('H:i:s') >= $schedule['opens_at'].':00'
            && $end->format('H:i:s') <= $schedule['closes_at'].':00'
            && $end->format('H:i:s') > $start->format('H:i:s')
            && $end->toDateString() === $start->toDateString();
    }

    public function validateBooking(ServiceCenter $center, CarbonImmutable $start, int $durationMinutes): void
    {
        if ($this->allows($center, $start, $durationMinutes)) {
            return;
        }

        $schedule = $this->handle($center, $start->toDateString());
        $message = match (true) {
            $schedule['source'] === 'unconfigured' => 'مواعيد العمل غير محددة لهذا اليوم. اختر يومًا آخر.',
            $schedule['is_closed'] => 'المركز مغلق في اليوم المختار. اختر يومًا آخر.',
            default => 'يجب أن يبدأ الحجز وينتهي بالكامل خلال مواعيد العمل بتوقيت مصر.',
        };
        throw ValidationException::withMessages(['scheduled_at' => $message]);
    }
}
