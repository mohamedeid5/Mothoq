<?php

namespace App\Data\ServiceCenters;

use Illuminate\Support\Arr;

final readonly class UpdateServiceCenterData
{
    private const ATTRIBUTE_FIELDS = [
        'city_id',
        'name',
        'description',
        'phone',
        'whatsapp',
        'address',
        'latitude',
        'longitude',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            cityId: array_key_exists('city_id', $attributes) ? (int) $attributes['city_id'] : null,
            name: array_key_exists('name', $attributes) ? (string) $attributes['name'] : null,
            description: isset($attributes['description']) ? (string) $attributes['description'] : null,
            phone: array_key_exists('phone', $attributes) ? (string) $attributes['phone'] : null,
            whatsapp: isset($attributes['whatsapp']) ? (string) $attributes['whatsapp'] : null,
            address: array_key_exists('address', $attributes) ? (string) $attributes['address'] : null,
            latitude: isset($attributes['latitude']) ? (float) $attributes['latitude'] : null,
            longitude: isset($attributes['longitude']) ? (float) $attributes['longitude'] : null,
            serviceIds: array_key_exists('service_ids', $attributes)
                ? array_map(intval(...), $attributes['service_ids'])
                : null,
            carBrandIds: array_key_exists('car_brand_ids', $attributes)
                ? array_map(intval(...), $attributes['car_brand_ids'])
                : null,
            providedFields: array_values(array_intersect(array_keys($attributes), self::ATTRIBUTE_FIELDS)),
        );
    }

    /**
     * @param  list<int>|null  $serviceIds
     * @param  list<int>|null  $carBrandIds
     * @param  list<string>  $providedFields
     */
    public function __construct(
        public ?int $cityId,
        public ?string $name,
        public ?string $description,
        public ?string $phone,
        public ?string $whatsapp,
        public ?string $address,
        public ?float $latitude,
        public ?float $longitude,
        public ?array $serviceIds,
        public ?array $carBrandIds,
        private array $providedFields,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return Arr::only([
            'city_id' => $this->cityId,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ], $this->providedFields);
    }
}
