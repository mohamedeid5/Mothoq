<?php

namespace App\Data\ServiceCenters;

final readonly class UpdateOpeningHoursData
{
    /**
     * @param  array{opening_hours: list<array{day: string, is_closed: bool|int|string, opens_at: ?string, closes_at: ?string}>}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(array_map(
            OpeningHourData::fromArray(...),
            $attributes['opening_hours'],
        ));
    }

    /** @param list<OpeningHourData> $openingHours */
    public function __construct(public array $openingHours) {}
}
