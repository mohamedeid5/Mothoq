<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\DayOfWeek;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OpeningHourControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_cannot_update_opening_hours(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.admin.service-centers.opening-hours.update', $serviceCenter),
                $this->validPayload(),
            )
            ->assertForbidden();
    }

    public function test_admin_updates_any_service_centers_opening_hours(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(
                route('api.v1.admin.service-centers.opening-hours.update', $serviceCenter),
                $this->validPayload(),
            )
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.6.day', DayOfWeek::Friday->value)
            ->assertJsonPath('data.6.day_label', DayOfWeek::Friday->label());

        $this->assertDatabaseCount('opening_hours', 7);
    }

    /** @return array{opening_hours: list<array{day: string, is_closed: bool, opens_at: ?string, closes_at: ?string}>} */
    private function validPayload(): array
    {
        return [
            'opening_hours' => array_map(
                fn (DayOfWeek $day): array => [
                    'day' => $day->value,
                    'is_closed' => $day === DayOfWeek::Friday,
                    'opens_at' => '10:00',
                    'closes_at' => '20:00',
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
