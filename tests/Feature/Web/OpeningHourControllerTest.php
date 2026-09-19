<?php

namespace Tests\Feature\Web;

use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OpeningHourControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_updates_their_opening_hours_from_the_edit_page(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('owner.service-centers.edit', $serviceCenter))
            ->assertOk()
            ->assertSee('مواعيد العمل')
            ->assertSee(DayOfWeek::Saturday->label())
            ->assertSee(route('owner.service-centers.opening-hours.update', $serviceCenter));

        $this->actingAs($owner)
            ->patch(
                route('owner.service-centers.opening-hours.update', $serviceCenter),
                $this->validPayload(),
            )
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حفظ مواعيد العمل بنجاح.');

        $this->assertDatabaseCount('opening_hours', 7);
    }

    public function test_owner_cannot_update_another_owners_opening_hours(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingAs($owner)
            ->patch(
                route('owner.service-centers.opening-hours.update', $otherCenter),
                $this->validPayload(),
            )
            ->assertNotFound();
    }

    public function test_admin_updates_any_service_centers_opening_hours(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.service-centers.edit', $serviceCenter))
            ->assertOk()
            ->assertSee(route('admin.service-centers.opening-hours.update', $serviceCenter));

        $this->actingAs($admin)
            ->patch(
                route('admin.service-centers.opening-hours.update', $serviceCenter),
                $this->validPayload(),
            )
            ->assertRedirect(route('admin.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حفظ مواعيد العمل بنجاح.');

        $this->assertDatabaseCount('opening_hours', 7);
    }

    public function test_public_center_page_displays_saved_opening_hours(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();
        OpeningHour::factory()->for($serviceCenter)->create([
            'day_of_week' => DayOfWeek::Saturday,
            'opens_at' => '09:00',
            'closes_at' => '18:00',
        ]);
        OpeningHour::factory()->for($serviceCenter)->closed()->create([
            'day_of_week' => DayOfWeek::Friday,
        ]);

        $this->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertSee('مواعيد العمل')
            ->assertSee(DayOfWeek::Saturday->label())
            ->assertSee('09:00 - 18:00')
            ->assertSee(DayOfWeek::Friday->label())
            ->assertSee('مغلق');
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
}
