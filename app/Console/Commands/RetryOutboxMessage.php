<?php

namespace App\Console\Commands;

use App\Enums\OutboxStatus;
use App\Models\OutboxMessage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('outbox:retry {id : Failed message ID to retry after review}')]
#[Description('Reset a failed outbox message; a previously accepted email may be sent again')]
class RetryOutboxMessage extends Command
{
    public function handle(): int
    {
        $updated = OutboxMessage::query()->whereKey($this->argument('id'))
            ->where('status', OutboxStatus::Failed)->update([
                'status' => OutboxStatus::Pending,
                'attempts' => 0,
                'available_at' => now(),
                'locked_until' => null,
                'lock_token' => null,
            ]);

        if ($updated === 0) {
            $this->components->error('No failed message found with that ID.');

            return self::FAILURE;
        }

        $this->components->info('Message scheduled for retry. Provider acceptance from an earlier attempt may cause a duplicate.');

        return self::SUCCESS;
    }
}
