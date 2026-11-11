<?php

namespace App\Console\Commands;

use App\Jobs\SendOutboxMessage;
use App\Models\OutboxMessage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('outbox:dispatch {--limit=100 : Maximum messages per run}')]
#[Description('Queue due outbox messages and recover expired processing leases')]
class DispatchOutboxMessages extends Command
{
    public function handle(): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->components->error('The limit must be between 1 and 1000.');

            return self::FAILURE;
        }

        $ids = OutboxMessage::query()->ready()->orderBy('available_at')->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            try {
                SendOutboxMessage::dispatch($id);
            } catch (Throwable $exception) {
                Log::error('Outbox dispatch failed; messages remain recoverable.', ['exception_class' => $exception::class]);
                $this->components->error('Queue unavailable. Outbox messages remain pending for a later run.');

                return self::FAILURE;
            }
        }

        $this->components->info("Considered {$ids->count()} outbox messages for dispatch.");

        return self::SUCCESS;
    }
}
