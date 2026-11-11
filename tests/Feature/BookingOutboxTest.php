<?php

namespace Tests\Feature;

use App\Actions\Bookings\CreateBookingAction;
use App\Data\Bookings\CreateBookingData;
use App\Enums\BookingNotificationType;
use App\Enums\DayOfWeek;
use App\Models\OpeningHour;
use App\Models\OutboxMessage;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class BookingOutboxTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creation_and_replay_save_one_notification_per_recipient_without_contacting_queue(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        [$center, $service] = $this->center();
        $customer = User::factory()->create();
        Queue::fake();
        Mail::fake();
        $this->actingAs($customer)->withHeader('Idempotency-Key', (string) Str::uuid());
        $payload = ['service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-11-20T10:00'];

        $first = $this->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated();
        $second = $this->postJson(route('api.v1.bookings.store', $center->slug), $payload)->assertCreated();

        $this->assertSame($first->getContent(), $second->getContent());
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('outbox_messages', 2);
        $this->assertDatabaseHas('outbox_messages', [
            'booking_id' => $first->json('data.id'), 'type' => BookingNotificationType::CustomerConfirmation->value,
            'recipient' => $customer->email, 'status' => 'pending',
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'booking_id' => $first->json('data.id'), 'type' => BookingNotificationType::OwnerNotification->value,
            'recipient' => $center->owner->email, 'status' => 'pending',
        ]);
        Queue::assertNothingPushed();
        Mail::assertNothingOutgoing();
    }

    public function test_outbox_insert_failure_rolls_back_booking_messages_and_idempotency_result(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        [$center, $service] = $this->center();
        Exceptions::fake();
        OutboxMessage::creating(function (OutboxMessage $message): void {
            if ($message->type === BookingNotificationType::OwnerNotification) {
                throw new RuntimeException('Outbox insert failed.');
            }
        });

        $this->actingAs(User::factory()->create())->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson(route('api.v1.bookings.store', $center->slug), [
                'service_id' => $service->id, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-11-20T10:00',
            ])->assertInternalServerError();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_outer_transaction_rollback_also_discards_notification_intent(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        [$center, $service] = $this->center();
        $customer = User::factory()->create();
        $data = new CreateBookingData($service->id, '01012345678', '2026-11-20T10:00', null);

        DB::beginTransaction();
        try {
            app(CreateBookingAction::class)->handle($customer, $center, $data);
            $this->assertDatabaseCount('outbox_messages', 2);
        } finally {
            DB::rollBack();
        }

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    public function test_direct_creation_without_owner_records_only_customer_notification(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        [$center, $service] = $this->center();
        $center->update(['owner_id' => null]);
        $customer = User::factory()->create();

        $booking = app(CreateBookingAction::class)->handle(
            $customer, $center, new CreateBookingData($service->id, '01012345678', '2026-11-20T10:00', null),
        );

        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseHas('outbox_messages', [
            'booking_id' => $booking->id, 'recipient' => $customer->email,
            'type' => BookingNotificationType::CustomerConfirmation->value,
        ]);
    }

    /** @return array{ServiceCenter, Service} */
    private function center(): array
    {
        $center = ServiceCenter::factory()->published()->create(['booking_capacity' => 1]);
        $service = Service::factory()->create();
        $center->services()->attach($service, ['duration_minutes' => 30]);
        OpeningHour::factory()->for($center)->create(['day_of_week' => DayOfWeek::Friday]);

        return [$center, $service];
    }
}
