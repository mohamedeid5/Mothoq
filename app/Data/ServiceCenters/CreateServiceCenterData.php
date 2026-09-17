<?php

namespace App\Data\ServiceCenters;

final readonly class CreateServiceCenterData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            ownerId: (int) $attributes['owner_id'],
            cityId: (int) $attributes['city_id'],
            name: (string) $attributes['name'],
            description: isset($attributes['description']) ? (string) $attributes['description'] : null,
            phone: (string) $attributes['phone'],
            whatsapp: isset($attributes['whatsapp']) ? (string) $attributes['whatsapp'] : null,
            address: (string) $attributes['address'],
            latitude: isset($attributes['latitude']) ? (float) $attributes['latitude'] : null,
            longitude: isset($attributes['longitude']) ? (float) $attributes['longitude'] : null,
        );
    }

    public function __construct(
        public int $ownerId,
        public int $cityId,
        public string $name,
        public ?string $description,
        public string $phone,
        public ?string $whatsapp,
        public string $address,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'owner_id' => $this->ownerId,
            'city_id' => $this->cityId,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
