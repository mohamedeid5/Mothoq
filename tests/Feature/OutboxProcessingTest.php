<?php

namespace Tests\Feature;

use App\Actions\Bookings\ProcessOutboxMessageAction;
use App\Enums\BookingNotificationType;
use App\Enums\OutboxStatus;
use App\Jobs\SendOutboxMessage;
use App\Models\OutboxMessage;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class OutboxProcessingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{BookingNotificationType}> */
    public static function notificationTypes(): array
    {
        return [
            'customer' => [BookingNotificationType::CustomerConfirmation],
            'owner' => [BookingNotificationType::OwnerNotification],
        ];
    }

    #[DataProvider('notificationTypes')]
    public function test_job_sends_snapshot_once_and_skips_completed_messages(BookingNotificationType $type): void
    {
        $message = OutboxMessage::factory()->create(['type' => $type]);
        $message->booking->update(['scheduled_at' => '2026-12-20 15:00:00']);
        $message->booking->customer->update(['name' => 'اسم جديد', 'email' => 'changed@example.com']);
        $transport = Mail::mailer()->getSymfonyTransport();

        $job = new SendOutboxMessage($message->id);
        $job->handle(app(ProcessOutboxMessageAction::class));
        $job->handle(app(ProcessOutboxMessageAction::class));

        $this->assertCount(1, $transport->messages());
        $email = $transport->messages()->first()->getOriginalMessage();
        $this->assertSame('customer@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('2026-11-20 10:00', $email->getHtmlBody());
        $this->assertStringNotContainsString('2026-12-20', $email->getHtmlBody());
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'sent', 'attempts' => 1, 'lock_token' => null]);
        $this->assertNotNull($message->fresh()->sent_at);
    }

    public function test_provider_failure_backs_off_then_recovers_without_losing_the_message(): void
    {
        $this->travelTo('2026-11-09 12:00:00');
        $message = OutboxMessage::factory()->create();
        $transport = new class extends ArrayTransport
        {
            public bool $unavailable = true;

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                if ($this->unavailable) {
                    throw new RuntimeException('Provider unavailable: private details must not be saved.');
                }

                return parent::send($message, $envelope);
            }
        };
        Mail::mailer()->setSymfonyTransport($transport);
        $processor = app(ProcessOutboxMessageAction::class);

        $processor->handle($message->id);

        $this->assertDatabaseHas('outbox_messages', [
            'id' => $message->id, 'status' => 'pending', 'attempts' => 1,
            'available_at' => '2026-11-09 12:01:00', 'last_error' => RuntimeException::class, 'sent_at' => null,
        ]);
        $transport->unavailable = false;
        $processor->handle($message->id);
        $this->assertCount(0, $transport->messages());

        $this->travelTo('2026-11-09 12:01:00');
        $processor->handle($message->id);

        $this->assertCount(1, $transport->messages());
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'sent', 'attempts' => 2]);
    }

    public function test_crash_after_provider_acceptance_can_duplicate_delivery_after_lease_expires(): void
    {
        $this->travelTo('2026-11-09 12:00:00');
        $message = OutboxMessage::factory()->create();
        $transport = Mail::mailer()->getSymfonyTransport();
        $crash = true;
        DB::listen(function (QueryExecuted $query) use (&$crash): void {
            if ($crash && str_starts_with($query->sql, 'update') && str_contains($query->sql, 'outbox_messages') && in_array('sent', $query->bindings, true)) {
                $crash = false;
                throw new RuntimeException('Simulated crash before the sent transaction commits.');
            }
        });
        $processor = app(ProcessOutboxMessageAction::class);

        try {
            $processor->handle($message->id);
            $this->fail('The simulated crash should interrupt acknowledgment.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated crash before the sent transaction commits.', $exception->getMessage());
        }

        $this->assertCount(1, $transport->messages());
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'processing', 'sent_at' => null]);
        $processor->handle($message->id);
        $this->assertCount(1, $transport->messages());

        $this->travel(181)->seconds();
        $processor->handle($message->id);

        $this->assertCount(2, $transport->messages());
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'sent', 'attempts' => 2]);
    }

    public function test_stale_worker_cannot_acknowledge_another_workers_claim(): void
    {
        $message = OutboxMessage::factory()->create();
        $newToken = (string) Str::uuid();
        $transport = new class($message->id, $newToken) extends ArrayTransport
        {
            public function __construct(private int $messageId, private string $token)
            {
                parent::__construct();
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                OutboxMessage::query()->whereKey($this->messageId)->update(['lock_token' => $this->token]);

                return parent::send($message, $envelope);
            }
        };
        Mail::mailer()->setSymfonyTransport($transport);

        app(ProcessOutboxMessageAction::class)->handle($message->id);

        $this->assertCount(1, $transport->messages());
        $this->assertDatabaseHas('outbox_messages', [
            'id' => $message->id, 'status' => 'processing', 'lock_token' => $newToken, 'sent_at' => null,
        ]);
    }

    public function test_final_provider_failure_requires_manual_retry(): void
    {
        $message = OutboxMessage::factory()->create(['attempts' => 4]);
        $transport = new class extends ArrayTransport
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new RuntimeException('Provider unavailable.');
            }
        };
        Mail::mailer()->setSymfonyTransport($transport);

        app(ProcessOutboxMessageAction::class)->handle($message->id);

        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'failed', 'attempts' => 5]);
        $this->artisan('outbox:retry', ['id' => $message->id])->assertSuccessful();
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'pending', 'attempts' => 0]);
    }

    public function test_expired_last_attempt_is_flagged_for_review_without_resending(): void
    {
        $message = OutboxMessage::factory()->create([
            'status' => OutboxStatus::Processing, 'attempts' => 5, 'locked_until' => now()->subSecond(),
            'lock_token' => (string) Str::uuid(),
        ]);
        $transport = Mail::mailer()->getSymfonyTransport();

        app(ProcessOutboxMessageAction::class)->handle($message->id);

        $this->assertCount(0, $transport->messages());
        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'failed', 'attempts' => 5, 'lock_token' => null]);
    }

    public function test_dispatch_only_selects_due_messages_and_expired_leases(): void
    {
        Queue::fake();
        $due = OutboxMessage::factory()->create();
        $expired = OutboxMessage::factory()->create(['status' => OutboxStatus::Processing, 'locked_until' => now()->subSecond()]);
        OutboxMessage::factory()->create(['available_at' => now()->addHour()]);
        OutboxMessage::factory()->create(['status' => OutboxStatus::Processing, 'locked_until' => now()->addMinute()]);
        OutboxMessage::factory()->sent()->create();
        OutboxMessage::factory()->failed()->create();

        $this->artisan('outbox:dispatch')->assertSuccessful();

        Queue::assertPushed(SendOutboxMessage::class, 2);
        Queue::assertPushedOn('emails', SendOutboxMessage::class, fn (SendOutboxMessage $job): bool => $job->messageId === $due->id);
        Queue::assertPushed(SendOutboxMessage::class, fn (SendOutboxMessage $job): bool => $job->messageId === $expired->id);
        $this->assertNull($due->fresh()->sent_at);
    }

    public function test_queue_failure_leaves_message_recoverable(): void
    {
        $message = OutboxMessage::factory()->create();
        Queue::shouldReceive('connection')->once()->andThrow(new RuntimeException('Queue unavailable.'));

        $this->artisan('outbox:dispatch')->assertFailed();

        $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'pending', 'attempts' => 0, 'sent_at' => null]);
    }

    public function test_retry_command_cannot_reset_a_sent_message(): void
    {
        $message = OutboxMessage::factory()->sent()->create();

        $this->artisan('outbox:retry', ['id' => $message->id])->assertFailed();

        $this->assertSame(OutboxStatus::Sent, $message->fresh()->status);
    }

    public function test_dispatch_rejects_an_invalid_batch_limit(): void
    {
        Queue::fake();

        $this->artisan('outbox:dispatch', ['--limit' => 0])->assertFailed();

        Queue::assertNothingPushed();
    }
}
