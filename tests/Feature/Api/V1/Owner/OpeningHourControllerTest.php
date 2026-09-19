<?php

namespace Tests\Feature\Api\V1\Owner;

use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OpeningHourControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_update_opening_hours(): void
    {
        $serviceCenter = ServiceCenter::factory()->create();

        $this->patchJson(
            route('api.v1.owner.service-centers.opening-hours.update', $serviceCenter),
            $this->validPayload(),
        )->assertUnauthorized();
    }

    public function test_owner_cannot_update_another_owners_opening_hours(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.opening-hours.update', $otherCenter),
                $this->validPayload(),
            )
            ->assertNotFound();
    }

    public function test_owner_replaces_the_complete_weekly_schedule(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        OpeningHour::factory()->for($serviceCenter)->create([
            'day_of_week' => DayOfWeek::Saturday,
            'opens_at' => '08:00',
            'closes_at' => '12:00',
        ]);

        $response = $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.opening-hours.update', $serviceCenter),
                $this->validPayload(),
            );

        $response
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.day', DayOfWeek::Saturday->value)
            ->assertJsonPath('data.0.opens_at', '09:00')
            ->assertJsonPath('data.6.day', DayOfWeek::Friday->value)
            ->assertJsonPath('data.6.is_closed', true)
            ->assertJsonPath('data.6.opens_at', null)
            ->assertJsonPath('data.6.closes_at', null);

        $this->assertDatabaseCount('opening_hours', 7);
        $this->assertDatabaseHas('opening_hours', [
            'service_center_id' => $serviceCenter->id,
            'day_of_week' => DayOfWeek::Saturday->value,
            'opens_at' => '09:00',
            'closes_at' => '18:00',
            'is_closed' => false,
        ]);
        $this->assertDatabaseHas('opening_hours', [
            'service_center_id' => $serviceCenter->id,
            'day_of_week' => DayOfWeek::Friday->value,
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);
    }

    public function test_opening_hours_require_all_unique_days(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $openingHours = $this->validPayload()['opening_hours'];
        $openingHours[1]['day'] = DayOfWeek::Saturday->value;

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.opening-hours.update', $serviceCenter),
                ['opening_hours' => $openingHours],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('opening_hours.1.day');

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.opening-hours.update', $serviceCenter),
                ['opening_hours' => array_slice($openingHours, 0, 6)],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('opening_hours');

        $this->assertDatabaseCount('opening_hours', 0);
    }

    public function test_open_days_require_valid_times_and_close_after_opening(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $openingHours = $this->validPayload()['opening_hours'];
        $openingHours[0]['opens_at'] = null;
        $openingHours[1]['closes_at'] = null;
        $openingHours[2]['opens_at'] = '18:00';
        $openingHours[2]['closes_at'] = '09:00';
        $openingHours[3]['opens_at'] = 'invalid';

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.opening-hours.update', $serviceCenter),
                ['opening_hours' => $openingHours],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'opening_hours.0.opens_at',
                'opening_hours.1.closes_at',
                'opening_hours.2.closes_at',
                'opening_hours.3.opens_at',
            ]);

        $this->assertDatabaseCount('opening_hours', 0);
    }

    /** @return array{opening_hours: list<array{day: string, is_closed: bool, opens_at: ?string, closes_at: ?string}>} */
    private function validPayload(): array
    {
        return [
            'opening_hours' => array_map(
                fn (DayOfWeek $day): array => [
                    'day' => $day->value,
                    'is_closed' => $day === DayOfWeek::Friday,
                    'opens_at' => '09:00',
                    'closes_at' => '18:00',
                ],
                DayOfWeek::cases(),
            ),
        ];
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
