<?php

namespace App\Actions\Bookings;

use App\Data\Bookings\BookingMailData;
use App\Enums\BookingNotificationType;
use App\Enums\OutboxStatus;
use App\Mail\BookingCreatedConfirmation;
use App\Mail\NewBookingReceived;
use App\Models\OutboxMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ProcessOutboxMessageAction
{
    public function handle(int $messageId): void
    {
        $message = $this->claim($messageId);
        if ($message === null) {
            return;
        }

        try {
            $details = BookingMailData::fromArray($message->payload);
            $mail = match ($message->type) {
                BookingNotificationType::CustomerConfirmation => new BookingCreatedConfirmation($details),
                BookingNotificationType::OwnerNotification => new NewBookingReceived($details),
            };

            $sent = Mail::to($message->recipient)->sendNow($mail);
            if ($sent === null) {
                throw new RuntimeException('Mail delivery was suppressed.');
            }
        } catch (Throwable $exception) {
            $this->retryLater($message, $exception);

            return;
        }

        /** A crash after provider acceptance can cause a duplicate on recovery. */
        DB::transaction(function () use ($message): void {
            OutboxMessage::query()->whereKey($message->id)
                ->where('status', OutboxStatus::Processing)
                ->where('lock_token', $message->lock_token)
                ->update([
                    'status' => OutboxStatus::Sent,
                    'sent_at' => now(),
                    'lock_token' => null,
                    'locked_until' => null,
                    'last_error' => null,
                ]);
        }, 3);
    }

    private function claim(int $messageId): ?OutboxMessage
    {
        return DB::transaction(function () use ($messageId): ?OutboxMessage {
            $message = OutboxMessage::query()->whereKey($messageId)->ready()->lockForUpdate()->first();
            if ($message === null) {
                return null;
            }

            if ($message->attempts >= OutboxMessage::MAX_ATTEMPTS) {
                $message->update([
                    'status' => OutboxStatus::Failed,
                    'locked_until' => null,
                    'lock_token' => null,
                    'last_error' => 'Attempt limit reached after an expired lease; delivery may have succeeded.',
                ]);
                Log::error('Outbox message requires review.', ['outbox_id' => $message->id]);

                return null;
            }

            $message->update([
                'status' => OutboxStatus::Processing,
                'attempts' => $message->attempts + 1,
                'lock_token' => (string) Str::uuid(),
                'locked_until' => now()->addSeconds(OutboxMessage::LEASE_SECONDS),
            ]);

            return $message;
        }, 3);
    }

    private function retryLater(OutboxMessage $message, Throwable $exception): void
    {
        $failed = $message->attempts >= OutboxMessage::MAX_ATTEMPTS;
        $delay = [60, 300, 900, 3600][min($message->attempts - 1, 3)];
        $updated = OutboxMessage::query()->whereKey($message->id)
            ->where('status', OutboxStatus::Processing)
            ->where('lock_token', $message->lock_token)
            ->update([
                'status' => $failed ? OutboxStatus::Failed : OutboxStatus::Pending,
                'available_at' => now()->addSeconds($delay),
                'lock_token' => null,
                'locked_until' => null,
                'last_error' => $exception::class,
            ]);

        if ($updated > 0) {
            Log::log($failed ? 'error' : 'warning', 'Outbox delivery attempt failed.', [
                'outbox_id' => $message->id,
                'attempt' => $message->attempts,
                'exception_class' => $exception::class,
            ]);
        }
    }
}
