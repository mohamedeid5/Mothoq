<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Enums\ServiceCenterStatus;
use App\Mail\BookingCreatedConfirmation;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingIdempotencyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_retry_returns_exact_original_response_without_new_booking_or_mail(): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        [$center, $service] = $this->center();
        $customer = User::factory()->create();
        $key = (string) Str::uuid();
        Mail::fake();
        $payload = $this->payload($service);

        $first = $this->actingAs($customer)->withHeader('Idempotency-Key', $key)
            ->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated();
        $bookingId = $first->json('data.id');
        $originalBody = $first->getContent();
        $this->assertDatabaseHas('idempotency_keys', [
            'customer_id' => $customer->id, 'key_hash' => hash('sha256', $key),
            'booking_id' => $bookingId, 'response_status' => 201,
        ]);

        Booking::findOrFail($bookingId)->update(['status' => BookingStatus::Accepted]);
        $center->update(['status' => ServiceCenterStatus::Draft]);
        DB::table('service_centers')->where('id', $center->id)->update(['booking_capacity' => null]);
        $replay = $this->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated();

        $this->assertSame($originalBody, $replay->getContent());
        $this->assertSame($bookingId, $replay->json('data.id'));
        $this->assertDatabaseCount('bookings', 1);
        Mail::assertQueued(BookingCreatedConfirmation::class, 1);
        Mail::assertQueued(NewBookingReceived::class, 1);
    }

    public function test_equivalent_local_datetime_reuses_the_original_result(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $key = (string) Str::uuid();
        Mail::fake();
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', $key);

        $first = $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        $payload = $this->payload($service);
        $payload['scheduled_at'] = '2026-09-25 10:00:00';
        $second = $this->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated();

        $this->assertSame($first->getContent(), $second->getContent());
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_retry_succeeds_after_appointment_passes_within_retention_window(): void
    {
        $this->travelTo('2026-09-25 09:00:00');
        [$center, $service] = $this->center();
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid());
        Mail::fake();
        $first = $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();

        $this->travelTo('2026-09-25 12:00:00');
        $second = $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();

        $this->assertSame($first->getContent(), $second->getContent());
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_same_key_with_different_appointment_or_service_returns_conflict(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $otherService = Service::factory()->create();
        $center->services()->attach($otherService, ['duration_minutes' => 60]);
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid());
        Mail::fake();
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();

        $differentTime = $this->payload($service);
        $differentTime['scheduled_at'] = '2026-09-25T12:00';
        $this->postJson(route('api.v1.bookings.store', $center->slug), $differentTime)
            ->assertStatus(409)->assertJsonValidationErrors('idempotency_key');
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($otherService))
            ->assertStatus(409)->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_different_keys_and_customers_are_independent(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $sharedKey = (string) Str::uuid();
        Mail::fake();

        $this->actingAs($customer)->withHeader('Idempotency-Key', $sharedKey)
            ->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        $this->actingAs($otherCustomer)->withHeader('Idempotency-Key', $sharedKey)
            ->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        $later = $this->payload($service);
        $later['scheduled_at'] = '2026-09-25T12:00';
        $this->actingAs($customer)->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson(route('api.v1.bookings.store', $center->slug), $later)->assertCreated();

        $this->assertDatabaseCount('bookings', 3);
        $this->assertDatabaseCount('idempotency_keys', 3);
    }

    public function test_failed_creation_does_not_commit_a_key_and_can_be_retried(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        DB::table('service_centers')->where('id', $center->id)->update(['booking_capacity' => null]);
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid());
        Mail::fake();

        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))
            ->assertUnprocessable()->assertJsonValidationErrors('booking_capacity');
        $this->assertDatabaseCount('idempotency_keys', 0);
        DB::table('service_centers')->where('id', $center->id)->update(['booking_capacity' => 2]);
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_missing_and_invalid_keys_are_rejected_without_booking(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $customer = User::factory()->create();
        $this->actingAs($customer)->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->withHeader('Idempotency-Key', 'not-a-uuid')
            ->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_same_key_for_another_center_conflicts_even_with_matching_other_fields(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        [$otherCenter] = $this->center();
        $otherCenter->services()->attach($service, ['duration_minutes' => 60]);
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid());
        Mail::fake();

        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        $this->postJson(route('api.v1.bookings.store', $otherCenter->slug), $this->payload($service))
            ->assertStatus(409)->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_lock_timeout_returns_retryable_error_without_claiming_a_key(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        DB::connection()->beforeExecuting(function (string $sql, array $bindings, Connection $connection): void {
            if (str_starts_with(strtolower($sql), 'insert into') && str_contains($sql, 'idempotency_keys')) {
                throw new QueryException((string) $connection->getName(), $sql, $bindings, new \PDOException('Lock wait timeout exceeded; try restarting transaction'));
            }
        });

        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))
            ->assertStatus(503)->assertHeader('Retry-After', '1');
        $this->assertDatabaseCount('idempotency_keys', 0);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_expired_key_can_be_reused_and_cleanup_does_not_delete_bookings(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $key = (string) Str::uuid();
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', $key);
        Mail::fake();
        $first = $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();

        $this->travelTo('2026-09-25 01:00:00');
        $later = $this->payload($service);
        $later['scheduled_at'] = '2026-09-26T12:00';
        $second = $this->postJson(route('api.v1.bookings.store', $center->slug), $later)->assertCreated();
        $this->assertNotSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('idempotency_keys', 1);

        $this->travelTo('2026-09-27');
        $this->artisan('bookings:prune-idempotency-keys')->assertSuccessful();
        $this->assertDatabaseCount('idempotency_keys', 0);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_deleted_booking_and_unknown_response_version_have_explicit_replay_results(): void
    {
        $this->travelTo('2026-09-24');
        [$center, $service] = $this->center();
        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid());
        Mail::fake();
        $first = $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertCreated();
        DB::table('idempotency_keys')->update(['response_version' => 2]);
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertStatus(409);

        DB::table('idempotency_keys')->update(['response_version' => 1]);
        Booking::findOrFail($first->json('data.id'))->delete();
        $this->postJson(route('api.v1.bookings.store', $center->slug), $this->payload($service))->assertStatus(410);
    }

    /** @return array{ServiceCenter, Service} */
    private function center(): array
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 2]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 60]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday, 'opens_at' => '09:00', 'closes_at' => '18:00']);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Saturday, 'opens_at' => '09:00', 'closes_at' => '18:00']);

        return [$center, $service];
    }

    /** @return array{service_id: int, customer_phone: string, scheduled_at: string, notes: string} */
    private function payload(Service $service): array
    {
        return [
            'service_id' => $service->id,
            'customer_phone' => '01012345678',
            'scheduled_at' => '2026-09-25T10:00',
            'notes' => 'فحص كامل',
        ];
    }
}
