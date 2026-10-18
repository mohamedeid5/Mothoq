<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('bookings:prune-idempotency-keys')]
#[Description('Remove booking idempotency results older than 24 hours')]
class PruneExpiredIdempotencyKeys extends Command
{
    public function handle(): int
    {
        $deleted = 0;
        do {
            $ids = DB::table('idempotency_keys')
                ->where('expires_at', '<=', now())
                ->orderBy('id')
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DB::table('idempotency_keys')
                ->whereIn('id', $ids)
                ->where('expires_at', '<=', now())
                ->delete();
        } while ($ids->count() === 1000);

        $this->components->info("Pruned {$deleted} expired booking idempotency keys.");

        return self::SUCCESS;
    }
}
