<?php

namespace App\Jobs;

use App\Actions\Bookings\ProcessOutboxMessageAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOutboxMessage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 120;

    public function __construct(public int $messageId)
    {
        $this->onQueue('emails');
    }

    public function uniqueId(): string
    {
        return (string) $this->messageId;
    }

    public function handle(ProcessOutboxMessageAction $messages): void
    {
        $messages->handle($this->messageId);
    }
}
