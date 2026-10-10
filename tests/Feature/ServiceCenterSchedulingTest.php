<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Enums\ServiceCenterStatus;
use App\Mail\BookingCreatedConfirmation;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\ServiceCenterScheduleException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceCenterSchedulingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, int, int, ?string}> */
    public static function boundaries(): array
    {
        return [
            'opening' => ['2026-09-25T09:00', 45, 201, '2026-09-25 09:00:00'],
            'before opening' => ['2026-09-25T08:59:59', 45, 422, null],
            'end exactly closing' => ['2026-09-25T17:15', 45, 201, '2026-09-25 17:15:00'],
            'end after closing' => ['2026-09-25T17:16', 45, 422, null],
            'start at closing' => ['2026-09-25T18:00', 45, 422, null],
            'short service fits' => ['2026-09-25T17:30', 30, 201, '2026-09-25 17:30:00'],
            'long service overruns' => ['2026-09-25T17:30', 90, 422, null],
            'UTC input rejected' => ['2026-09-25T06:00:00Z', 45, 422, null],
            'offset input rejected' => ['2026-09-25T09:00:00+03:00', 45, 422, null],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_booking_checks_full_database_duration_and_saves_egypt_local_time(string $start, int $duration, int $status, ?string $storedTime): void
    {
        $this->travelTo('2026-09-24 00:00:00');
        [$center, $service] = $this->centerWithService($duration);
        Mail::fake();

        $response = $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            ...$this->bookingPayload($service, $start),
            'duration_minutes' => 1,
        ]);

        $response->assertStatus($status);
        if ($status === 201) {
            $response->assertJsonPath('data.duration_minutes', $duration)->assertJsonMissingPath('data.scheduled_at_timezone');
            $this->assertDatabaseHas('bookings', ['scheduled_at' => $storedTime, 'duration_minutes' => $duration]);
            Mail::assertQueued(BookingCreatedConfirmation::class);
        } else {
            $response->assertJsonValidationErrors('scheduled_at');
            $this->assertDatabaseCount('bookings', 0);
            Mail::assertNothingQueued();
        }
    }

    /** @return array<string, array{bool, ?array, string, int}> */
    public static function exceptions(): array
    {
        return [
            'weekly closed' => [true, null, '2026-09-25T12:00', 422],
            'closed exception' => [false, ['is_closed' => true, 'opens_at' => null, 'closes_at' => null], '2026-09-25T12:00', 422],
            'opens closed weekday' => [true, ['is_closed' => false, 'opens_at' => '10:00', 'closes_at' => '14:00'], '2026-09-25T12:00', 201],
            'shortened exception rejects' => [false, ['is_closed' => false, 'opens_at' => '10:00', 'closes_at' => '14:00'], '2026-09-25T13:45', 422],
            'extended exception allows' => [false, ['is_closed' => false, 'opens_at' => '07:00', 'closes_at' => '20:00'], '2026-09-25T19:30', 201],
            'other date follows weekly' => [false, ['is_closed' => true, 'opens_at' => null, 'closes_at' => null], '2026-10-02T12:00', 201],
        ];
    }

    #[DataProvider('exceptions')]
    public function test_exception_precedence_is_enforced_for_bookings(bool $closed, ?array $exception, string $start, int $status): void
    {
        $this->travelTo('2026-09-24 00:00:00');
        [$center, $service] = $this->centerWithService(30);
        if ($closed) {
            $center->openingHours()->update(['is_closed' => true, 'opens_at' => null, 'closes_at' => null]);
        }
        if ($exception !== null) {
            $center->scheduleExceptions()->create(['date' => '2026-09-25', ...$exception]);
        }
        Mail::fake();

        $response = $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, $start));

        $response->assertStatus($status);
        $this->assertDatabaseCount('bookings', $status === 201 ? 1 : 0);
        if ($status === 422) {
            $response->assertJsonValidationErrors('scheduled_at');
            Mail::assertNothingQueued();
        }
    }

    public function test_missing_duration_prevents_booking_without_inventing_a_default(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(null);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), [
            ...$this->bookingPayload($service, '2026-09-25T12:00'), 'duration_minutes' => 30,
        ])->assertUnprocessable()->assertJsonValidationErrors('service_id');

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingQueued();
    }

    public function test_snapshot_survives_duration_changes_and_same_service_has_different_center_durations(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(45);
        $other = ServiceCenter::factory()->published()->create();
        $other->services()->attach($service, ['duration_minutes' => 90]);
        OpeningHour::factory()->for($other)->create(['day_of_week' => DayOfWeek::Friday]);
        $customer = User::factory()->create();
        Mail::fake();
        $payload = $this->bookingPayload($service, '2026-09-25T12:00');
        $this->actingAs($customer)->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated()->assertJsonPath('data.duration_minutes', 45);
        $this->actingAs($customer)->postJson(route('api.v1.bookings.store', $other->slug), $payload)->assertCreated()->assertJsonPath('data.duration_minutes', 90);

        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 120])->assertOk()->assertJsonPath('data.duration_minutes', 120);

        $this->assertDatabaseHas('bookings', ['service_center_id' => $center->id, 'duration_minutes' => 45]);
        $this->assertDatabaseHas('bookings', ['service_center_id' => $other->id, 'duration_minutes' => 90]);
        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'duration_minutes' => 120]);
    }

    public function test_exception_update_preserves_identity_and_delete_restores_weekly_schedule(): void
    {
        [$center] = $this->centerWithService(30);
        $url = route('api.v1.owner.service-centers.schedule-exceptions.store', $center);
        $this->actingAs($center->owner)->putJson($url, ['date' => '2026-09-25', 'is_closed' => true])->assertCreated();
        $exception = $center->scheduleExceptions()->sole();
        $createdAt = $exception->created_at;

        $this->putJson($url, ['date' => '2026-09-25', 'is_closed' => false, 'opens_at' => '10:00', 'closes_at' => '14:00', 'service_center_id' => 999])
            ->assertOk()->assertJsonPath('data.id', $exception->id);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
        $this->assertSame($createdAt->toISOString(), $exception->fresh()->created_at->toISOString());
        $this->getJson(route('api.v1.service-centers.schedule.show', ['serviceCenter' => $center->slug, 'date' => '2026-09-25']))
            ->assertOk()->assertJsonPath('data.source', 'exception')->assertJsonPath('data.opens_at', '10:00')->assertJsonMissingPath('data.timezone');

        $this->deleteJson(route('api.v1.owner.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertNoContent();
        $this->getJson(route('api.v1.service-centers.schedule.show', ['serviceCenter' => $center->slug, 'date' => '2026-09-25']))
            ->assertOk()->assertJsonPath('data.source', 'weekly')->assertJsonPath('data.opens_at', '09:00');
        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    public function test_public_schedule_marks_unconfigured_dates_unavailable_and_hides_unpublished_centers(): void
    {
        $center = ServiceCenter::factory()->published()->create();
        $url = route('api.v1.service-centers.schedule.show', ['serviceCenter' => $center->slug, 'date' => '2026-09-25']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.source', 'unconfigured')->assertJsonPath('data.is_closed', true);
        $center->update(['status' => ServiceCenterStatus::Draft]);
        $this->getJson($url)->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function privilegedPrefixes(): array
    {
        return ['owner' => ['owner'], 'admin' => ['admin']];
    }

    #[DataProvider('privilegedPrefixes')]
    public function test_schedule_management_rejects_guests_and_customers(string $prefix): void
    {
        [$center, $service] = $this->centerWithService(30);
        $exception = ServiceCenterScheduleException::factory()->for($center)->closed()->create();
        $urls = [
            ['putJson', route("api.v1.{$prefix}.service-centers.schedule-exceptions.store", $center), ['date' => '2026-09-25', 'is_closed' => true]],
            ['deleteJson', route("api.v1.{$prefix}.service-centers.schedule-exceptions.destroy", [$center, $exception]), []],
            ['patchJson', route("api.v1.{$prefix}.service-centers.services.duration.update", [$center, $service]), ['duration_minutes' => 45]],
            ['getJson', route("api.v1.{$prefix}.service-centers.schedule-exceptions.index", $center), []],
        ];
        foreach ($urls as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertUnauthorized();
        }
        $this->actingAs(User::factory()->create());
        foreach ($urls as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertForbidden();
        }
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'duration_minutes' => 30]);
    }

    public function test_owner_cannot_modify_another_center_or_cross_bind_an_exception(): void
    {
        [$center, $service] = $this->centerWithService(30);
        $other = ServiceCenter::factory()->create();
        $exception = ServiceCenterScheduleException::factory()->for($other)->closed()->create();
        $this->actingAs($center->owner);

        $this->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $other), ['date' => '2026-09-25', 'is_closed' => true])->assertNotFound();
        $this->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$other, $service]), ['duration_minutes' => 40])->assertNotFound();
        $this->deleteJson(route('api.v1.owner.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertNotFound();

        $this->assertModelExists($exception);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 1);
    }

    public function test_admin_can_manage_exception_and_duration_for_any_center(): void
    {
        [$center, $service] = $this->centerWithService(null);
        $this->actingAs(User::factory()->admin()->create());

        $this->patchJson(route('api.v1.admin.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 75])->assertOk()->assertJsonPath('data.duration_minutes', 75);
        $this->putJson(route('api.v1.admin.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', 'is_closed' => true])->assertCreated();
        $this->getJson(route('api.v1.admin.service-centers.schedule-exceptions.index', $center))->assertOk()->assertJsonCount(1, 'data');
        $exception = $center->scheduleExceptions()->sole();
        $this->deleteJson(route('api.v1.admin.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertNoContent();

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'duration_minutes' => 75]);
        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidExceptions(): array
    {
        return [
            'overnight' => [['is_closed' => false, 'opens_at' => '22:00', 'closes_at' => '02:00'], 'closes_at'],
            'zero interval' => [['is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '09:00'], 'closes_at'],
            'missing open' => [['is_closed' => false, 'closes_at' => '18:00'], 'opens_at'],
            'missing close' => [['is_closed' => false, 'opens_at' => '09:00'], 'closes_at'],
            'contradictory closed' => [['is_closed' => true, 'opens_at' => '09:00'], 'opens_at'],
            'invalid time' => [['is_closed' => false, 'opens_at' => '25:00', 'closes_at' => '27:00'], 'opens_at'],
            'invalid date' => [['date' => '2026-02-30', 'is_closed' => true], 'date'],
        ];
    }

    #[DataProvider('invalidExceptions')]
    public function test_invalid_exception_returns_422_without_writing(array $payload, string $field): void
    {
        $center = ServiceCenter::factory()->create();
        $this->actingAs($center->owner)->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', ...$payload])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    /** @return array<string, array{BookingStatus}> */
    public static function legacyBookings(): array
    {
        return ['pending' => [BookingStatus::Pending], 'accepted' => [BookingStatus::Accepted]];
    }

    #[DataProvider('legacyBookings')]
    public function test_unknown_active_booking_blocks_schedule_changes_without_mutating_history(BookingStatus $status): void
    {
        [$center, $service] = $this->centerWithService(30);
        $booking = Booking::factory()->for($center)->for($service)->create(['scheduled_at' => '2026-09-25 09:00:00', 'duration_minutes' => null, 'status' => $status]);
        $original = $booking->fresh()->getRawOriginal();

        $this->actingAs($center->owner)->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', 'is_closed' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('date');

        $this->assertSame($original, $booking->fresh()->getRawOriginal());
        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    public function test_conflicting_weekly_schedule_and_exception_deletion_roll_back(): void
    {
        [$center, $service] = $this->centerWithService(90);
        Booking::factory()->for($center)->for($service)->create(['scheduled_at' => '2026-09-25 17:00:00', 'duration_minutes' => 90]);
        $exception = $center->scheduleExceptions()->create(['date' => '2026-09-25', 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '19:00']);
        $center->openingHours()->update(['closes_at' => '17:30']);
        $this->actingAs($center->owner);

        $this->deleteJson(route('api.v1.owner.service-centers.schedule-exceptions.destroy', [$center, $exception]))->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->assertModelExists($exception);
        $this->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '18:00'])->assertUnprocessable();
        $this->assertSame('19:00', substr($exception->fresh()->closes_at, 0, 5));

        $exception->delete();
        $hours = array_map(fn (DayOfWeek $day): array => ['day' => $day->value, 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '18:00'], DayOfWeek::cases());
        $this->patchJson(route('api.v1.owner.service-centers.opening-hours.update', $center), ['opening_hours' => $hours])->assertUnprocessable()->assertJsonValidationErrors('opening_hours');
        $this->assertDatabaseCount('opening_hours', 1);
        $this->assertDatabaseHas('opening_hours', ['service_center_id' => $center->id, 'closes_at' => '17:30']);
    }

    public function test_weekly_change_respects_exception_and_stored_duration_snapshot(): void
    {
        [$center, $service] = $this->centerWithService(120);
        Booking::factory()->for($center)->for($service)->create(['scheduled_at' => '2026-09-25 17:30:00', 'duration_minutes' => 30]);
        $center->scheduleExceptions()->create(['date' => '2026-09-25', 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '18:00']);
        $hours = array_map(fn (DayOfWeek $day): array => ['day' => $day->value, 'is_closed' => true, 'opens_at' => null, 'closes_at' => null], DayOfWeek::cases());

        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.opening-hours.update', $center), ['opening_hours' => $hours])->assertOk();

        $this->assertDatabaseCount('opening_hours', 7);
        $this->assertDatabaseHas('bookings', ['service_center_id' => $center->id, 'duration_minutes' => 30]);
    }

    public function test_inactive_legacy_bookings_do_not_prevent_schedule_changes(): void
    {
        [$center, $service] = $this->centerWithService(30);
        foreach ([BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::Rejected] as $status) {
            Booking::factory()->for($center)->for($service)->create(['status' => $status, 'duration_minutes' => null]);
        }

        $this->actingAs($center->owner)->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', 'is_closed' => true])->assertCreated();

        $this->assertSame(3, Booking::query()->whereNull('duration_minutes')->count());
    }

    public function test_legacy_response_keeps_local_time_and_requires_duration_review(): void
    {
        $customer = User::factory()->create();
        Booking::factory()->for($customer, 'customer')->create(['scheduled_at' => '2026-09-25 12:00:00', 'duration_minutes' => null]);

        $this->actingAs($customer)->getJson(route('api.v1.bookings.index'))->assertOk()
            ->assertJsonPath('data.0.scheduled_at', '2026-09-25 12:00:00')
            ->assertJsonMissingPath('data.0.scheduled_at_timezone')
            ->assertJsonPath('data.0.schedule_requires_review', true)
            ->assertJsonPath('data.0.ends_at', null);
    }

    /** @return array<string, array{string, string, string, int, ?string}> */
    public static function localTimeCases(): array
    {
        return [
            'winter time unchanged' => ['2026-01-02T09:00', '09:00', '18:00', 201, '2026-01-02 09:00:00'],
            'summer time unchanged' => ['2026-09-25T09:00', '09:00', '18:00', 201, '2026-09-25 09:00:00'],
            'UTC date input rejected' => ['2026-09-24T22:30:00Z', '01:00', '05:00', 422, null],
            'spring missing time' => ['2026-04-24T00:30', '01:00', '05:00', 422, null],
            'autumn local time stored as entered' => ['2026-10-29T23:10', '21:00', '23:59', 201, '2026-10-29 23:10:00'],
            'explicit offset rejected' => ['2026-10-29T23:10:00+03:00', '21:00', '23:59', 422, null],
        ];
    }

    #[DataProvider('localTimeCases')]
    public function test_local_times_are_preserved_and_offset_inputs_are_rejected(string $start, string $opens, string $closes, int $status, ?string $storedTime): void
    {
        $this->travelTo('2025-12-01');
        [$center, $service] = $this->centerWithService(30);
        $center->openingHours()->delete();
        foreach (DayOfWeek::cases() as $day) {
            OpeningHour::factory()->for($center)->create(['day_of_week' => $day, 'opens_at' => $opens, 'closes_at' => $closes]);
        }
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, $start))->assertStatus($status);

        $this->assertDatabaseCount('bookings', $status === 201 ? 1 : 0);
        if ($storedTime !== null) {
            $this->assertDatabaseHas('bookings', ['scheduled_at' => $storedTime]);
        }
    }

    public function test_center_update_does_not_add_a_timezone_setting(): void
    {
        [$center] = $this->centerWithService(30);

        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.update', $center), ['timezone' => 'UTC'])
            ->assertOk()->assertJsonMissingPath('data.timezone');

        $this->assertArrayNotHasKey('timezone', $center->fresh()->getAttributes());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidDurationInputs(): array
    {
        return ['zero' => [0], 'negative' => [-5], 'fractional' => [1.5], 'too long' => [1440], 'text' => ['unknown']];
    }

    #[DataProvider('invalidDurationInputs')]
    public function test_duration_endpoint_rejects_invalid_values(mixed $duration): void
    {
        [$center, $service] = $this->centerWithService(30);

        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => $duration])
            ->assertUnprocessable()->assertJsonValidationErrors('duration_minutes');

        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'service_id' => $service->id, 'duration_minutes' => 30]);
    }

    public function test_duration_endpoint_cannot_attach_a_service_and_can_clear_duration(): void
    {
        [$center, $service] = $this->centerWithService(30);
        $otherService = Service::factory()->create();
        $this->actingAs($center->owner);

        $this->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$center, $otherService]), ['duration_minutes' => 30])->assertNotFound();
        $this->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => null])->assertOk()->assertJsonPath('data.duration_minutes', null);

        $this->assertDatabaseCount('service_service_center', 1);
        $this->assertDatabaseHas('service_service_center', ['service_center_id' => $center->id, 'duration_minutes' => null]);
    }

    public function test_duplicate_booking_matches_local_time_with_and_without_seconds(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(30);
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-09-25T09:00'))->assertCreated();

        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-09-25 09:00:00'))->assertUnprocessable()
            ->assertJsonPath('errors.scheduled_at.0', 'لديك حجز قائم في نفس المركز وفي نفس الموعد.');

        $this->assertDatabaseCount('bookings', 1);
        Mail::assertQueued(BookingCreatedConfirmation::class, 1);
    }

    public function test_exception_and_booking_use_the_same_local_calendar_date(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(30);
        $center->openingHours()->update(['is_closed' => true, 'opens_at' => null, 'closes_at' => null]);
        $center->scheduleExceptions()->create(['date' => '2026-09-25', 'is_closed' => false, 'opens_at' => '01:00', 'closes_at' => '05:00']);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-09-25T01:30:00'))
            ->assertCreated()->assertJsonPath('data.scheduled_at', '2026-09-25 01:30:00');

        $this->assertDatabaseHas('bookings', ['scheduled_at' => '2026-09-25 01:30:00']);
    }

    public function test_changed_duration_applies_only_to_later_bookings(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(30);
        $customer = User::factory()->create();
        Mail::fake();
        $this->actingAs($customer)->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-09-25T17:30'))->assertCreated();
        $this->actingAs($center->owner)->patchJson(route('api.v1.owner.service-centers.services.duration.update', [$center, $service]), ['duration_minutes' => 90])->assertOk();

        $this->actingAs($customer)->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-10-02T17:30'))->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', ['duration_minutes' => 30]);
    }

    public function test_public_schedule_returns_local_hours_and_rejects_invalid_date(): void
    {
        $center = ServiceCenter::factory()->published()->create();
        $center->scheduleExceptions()->create(['date' => '2026-10-29', 'is_closed' => false, 'opens_at' => '21:00', 'closes_at' => '23:59']);

        $this->getJson(route('api.v1.service-centers.schedule.show', ['serviceCenter' => $center->slug, 'date' => '2026-10-29']))
            ->assertOk()->assertJsonPath('data.is_available', true)->assertJsonPath('data.closes_at', '23:59');
        $this->getJson(route('api.v1.service-centers.schedule.show', ['serviceCenter' => $center->slug, 'date' => '2026-02-30']))
            ->assertUnprocessable()->assertJsonValidationErrors('date');
    }

    public function test_unrelated_date_exception_does_not_block_an_unknown_legacy_duration(): void
    {
        [$center, $service] = $this->centerWithService(30);
        Booking::factory()->for($center)->for($service)->create(['scheduled_at' => '2026-09-25 09:00:00', 'duration_minutes' => null]);

        $this->actingAs($center->owner)->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-26', 'is_closed' => true])->assertCreated();

        $this->assertDatabaseHas('bookings', ['duration_minutes' => null]);
    }

    public function test_duplicate_write_failure_returns_409_and_rolls_back(): void
    {
        $center = ServiceCenter::factory()->create();
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with(strtolower($query->sql), 'insert into') && str_contains($query->sql, 'service_center_schedule_exceptions')) {
                throw new UniqueConstraintViolationException($query->connectionName, $query->sql, $query->bindings, new \PDOException('Duplicate concurrent write'));
            }
        });

        $this->actingAs($center->owner)->putJson(route('api.v1.owner.service-centers.schedule-exceptions.store', $center), ['date' => '2026-09-25', 'is_closed' => true])
            ->assertConflict()->assertJsonValidationErrors('schedule');

        $this->assertDatabaseCount('service_center_schedule_exceptions', 0);
    }

    public function test_booking_response_and_label_keep_the_entered_local_time(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->centerWithService(30);
        Mail::fake();

        $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, '2026-09-25 09:00:00'))
            ->assertCreated()->assertJsonPath('data.scheduled_at', '2026-09-25 09:00:00')->assertJsonPath('data.ends_at', '2026-09-25 09:30:00');

        $this->assertDatabaseHas('bookings', ['scheduled_at' => '2026-09-25 09:00:00']);
        $this->assertSame('2026-09-25 09:00 بتوقيت مصر', Booking::query()->sole()->scheduleLabel());
    }

    /** @return array<string, array{string, int}> */
    public static function futureLocalTimes(): array
    {
        return ['current local minute' => ['2026-09-25T09:00', 422], 'next local minute' => ['2026-09-25T09:01', 201]];
    }

    #[DataProvider('futureLocalTimes')]
    public function test_future_validation_uses_egypt_current_time(string $start, int $status): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-25 06:00:00', 'UTC'));
        [$center, $service] = $this->centerWithService(30);
        Mail::fake();

        $response = $this->actingAs(User::factory()->create())->postJson(route('api.v1.bookings.store', $center->slug), $this->bookingPayload($service, $start));

        $response->assertStatus($status);
        $this->assertDatabaseCount('bookings', $status === 201 ? 1 : 0);
        if ($status === 422) {
            $response->assertJsonPath('errors.scheduled_at.0', 'موعد الحجز يجب أن يكون في المستقبل.');
            Mail::assertNothingQueued();
        }
    }

    /** @return array{ServiceCenter, Service} */
    private function centerWithService(?int $duration): array
    {
        $center = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => $duration]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);

        return [$center, $service];
    }

    /** @return array{service_id: int, customer_phone: string, scheduled_at: string} */
    private function bookingPayload(Service $service, string $start): array
    {
        return ['service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => $start];
    }
}
