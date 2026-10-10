<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Mail\BookingCreatedConfirmation;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingOpeningHoursTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, bool}> */
    public static function bookingTimes(): array
    {
        return [
            'before opening' => ['2026-09-25T08:59:59', false],
            'opening boundary' => ['2026-09-25T09:00:00', true],
            'during working hours' => ['2026-09-25T12:30:00', true],
            'before closing but duration overruns' => ['2026-09-25T17:59:59', false],
            'closing boundary' => ['2026-09-25T18:00:00', false],
            'after closing' => ['2026-09-25T18:01:00', false],
            'offset not accepted' => ['2026-09-25T11:00:00+02:00', false],
            'offset before opening also rejected' => ['2026-09-25T09:00:00+02:00', false],
        ];
    }

    #[DataProvider('bookingTimes')]
    public function test_api_enforces_opening_and_closing_boundaries(string $scheduledAt, bool $accepted): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        $customer = User::factory()->create();
        $center = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);
        Mail::fake();

        $response = $this->withToken($customer->createToken('Test')->plainTextToken)
            ->postJson(route('api.v1.bookings.store', $center->slug), [
                'service_id' => $service->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => $scheduledAt,
            ]);

        if ($accepted) {
            $response->assertCreated();
            $this->assertDatabaseCount('bookings', 1);
            Mail::assertQueued(BookingCreatedConfirmation::class);
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
            $this->assertDatabaseCount('bookings', 0);
            Mail::assertNothingQueued();
        }
    }

    /** @return array<string, array{bool, bool, ?string, ?string, string}> */
    public static function unavailableDays(): array
    {
        return [
            'closed' => [true, true, null, null, 'المركز مغلق في اليوم المختار. اختر يومًا آخر.'],
            'missing day' => [false, false, null, null, 'مواعيد العمل غير محددة لهذا اليوم. اختر يومًا آخر.'],
            'missing opening' => [true, false, null, '18:00', 'مواعيد العمل غير محددة لهذا اليوم. اختر يومًا آخر.'],
            'missing closing' => [true, false, '09:00', null, 'مواعيد العمل غير محددة لهذا اليوم. اختر يومًا آخر.'],
        ];
    }

    #[DataProvider('unavailableDays')]
    public function test_web_rejects_unavailable_days(bool $hasHours, bool $closed, ?string $opensAt, ?string $closesAt, string $message): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        $customer = User::factory()->create();
        $center = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);

        if ($hasHours && ! $closed && ($opensAt === null || $closesAt === null)) {
            $this->expectException(QueryException::class);
        }

        if ($hasHours) {
            OpeningHour::factory()->for($center)->create([
                'day_of_week' => DayOfWeek::Friday,
                'is_closed' => $closed,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ]);
        }

        Mail::fake();
        $this->actingAs($customer)->from(route('service-centers.show', $center->slug))
            ->post(route('bookings.store', $center->slug), [
                'service_id' => $service->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => '2026-09-25T12:00',
            ])->assertRedirect(route('service-centers.show', $center->slug))
            ->assertSessionHasErrors(['scheduled_at' => $message]);

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingQueued();
    }

    public function test_booking_form_shows_working_hours_closed_and_unconfigured_days(): void
    {
        $this->withoutVite();
        $customer = User::factory()->create();
        $center = ServiceCenter::factory()->published()->create();
        $center->services()->attach(Service::factory()->create());
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Saturday]);
        OpeningHour::factory()->for($center)->closed()->create(['day_of_week' => DayOfWeek::Friday]);

        $this->actingAs($customer)->get(route('service-centers.show', $center->slug))
            ->assertSee('مواعيد الحجز بتوقيت مصر')
            ->assertSee('09:00')->assertSee('18:00')
            ->assertSee('مغلق')->assertSee('غير محدد — غير متاح للحجز');
    }

    public function test_local_date_uses_its_own_weekday_and_stores_time_unchanged(): void
    {
        $this->travelTo('2026-09-23 10:00:00');
        $customer = User::factory()->create();
        $center = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        OpeningHour::factory()->for($center)->create([
            'day_of_week' => DayOfWeek::Thursday, 'opens_at' => '20:00', 'closes_at' => '23:00',
        ]);
        OpeningHour::factory()->for($center)->closed()->create(['day_of_week' => DayOfWeek::Friday]);
        Mail::fake();

        $this->withToken($customer->createToken('Test')->plainTextToken)
            ->postJson(route('api.v1.bookings.store', $center->slug), [
                'service_id' => $service->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => '2026-09-24T22:00:00',
            ])->assertCreated();

        $this->assertDatabaseHas('bookings', ['scheduled_at' => '2026-09-24 22:00:00']);
        Mail::assertQueued(BookingCreatedConfirmation::class);
    }
}
