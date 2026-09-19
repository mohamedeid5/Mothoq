<?php

namespace App\Data\ServiceCenters;

use App\Enums\DayOfWeek;

final readonly class OpeningHourData
{
    /**
     * @param  array{day: string, is_closed: bool|int|string, opens_at: ?string, closes_at: ?string}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $isClosed = filter_var($attributes['is_closed'], FILTER_VALIDATE_BOOLEAN);

        return new self(
            day: DayOfWeek::from($attributes['day']),
            isClosed: $isClosed,
            opensAt: $isClosed ? null : $attributes['opens_at'],
            closesAt: $isClosed ? null : $attributes['closes_at'],
        );
    }

    public function __construct(
        public DayOfWeek $day,
        public bool $isClosed,
        public ?string $opensAt,
        public ?string $closesAt,
    ) {}
}
