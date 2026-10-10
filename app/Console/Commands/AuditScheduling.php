<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditScheduling extends Command
{
    protected $signature = 'scheduling:audit';

    protected $description = 'Read-only counts of service durations and historical bookings requiring review, before or after migration';

    public function handle(): int
    {
        $assignments = DB::table('service_service_center');
        if (Schema::hasColumn('service_service_center', 'duration_minutes')) {
            $assignments->whereNull('duration_minutes');
        }

        $active = DB::table('bookings')->whereIn('status', ['pending', 'accepted']);
        $unknownDurations = clone $active;
        if (Schema::hasColumn('bookings', 'duration_minutes')) {
            $unknownDurations->whereNull('duration_minutes');
        }

        $this->table(['Metric', 'Count'], [
            ['services', DB::table('services')->count()],
            ['assignments_without_duration', $assignments->count()],
            ['active_bookings_without_duration', $unknownDurations->count()],
            ['total_bookings', DB::table('bookings')->count()],
        ]);
        $this->info('Read only: no durations, times, or statuses were changed.');

        return self::SUCCESS;
    }
}
