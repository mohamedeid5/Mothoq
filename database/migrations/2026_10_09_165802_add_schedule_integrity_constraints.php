<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return array<string, array<string, string>> */
    private function constraints(): array
    {
        $interval = "(is_closed = 1 AND opens_at IS NULL AND closes_at IS NULL) OR (is_closed = 0 AND opens_at IS NOT NULL AND closes_at IS NOT NULL AND opens_at >= '00:00' AND closes_at < '24:00' AND opens_at < closes_at)";
        if (DB::getDriverName() === 'sqlite') {
            $interval = "({$interval}) AND (is_closed = 1 OR (time(opens_at) IS NOT NULL AND time(closes_at) IS NOT NULL))";
        }
        $duration = 'duration_minutes IS NULL OR (duration_minutes BETWEEN 1 AND 1439)';

        return [
            'opening_hours' => [
                'opening_hours_interval_check' => $interval,
                'opening_hours_weekday_check' => "day_of_week IN ('saturday','sunday','monday','tuesday','wednesday','thursday','friday')",
            ],
            'service_center_schedule_exceptions' => ['schedule_exception_interval_check' => $interval],
            'service_service_center' => ['service_duration_check' => $duration],
            'bookings' => [
                'booking_duration_check' => $duration,
                'booking_timezone_check' => "scheduled_at_timezone IS NULL OR scheduled_at_timezone = 'UTC'",
            ],
        ];
    }

    public function up(): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Scheduling constraints support MySQL 8.0.16+ and SQLite.');
        }

        foreach ($this->constraints() as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                if (DB::table($table)->whereRaw("NOT ({$expression})")->exists()) {
                    throw new RuntimeException("Review invalid rows in {$table} before adding {$name}; no rows were repaired automatically.");
                }
            }
        }

        foreach ($this->constraints() as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                if ($driver === 'sqlite') {
                    $newExpression = preg_replace('/\b(is_closed|opens_at|closes_at|day_of_week|duration_minutes|scheduled_at_timezone)\b/', 'NEW.$1', $expression);
                    foreach (['INSERT', 'UPDATE'] as $operation) {
                        DB::unprepared("CREATE TRIGGER {$name}_{$operation} BEFORE {$operation} ON {$table} WHEN NOT ({$newExpression}) BEGIN SELECT RAISE(ABORT, '{$name}'); END");
                    }
                } else {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->constraints() as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                if (DB::getDriverName() === 'sqlite') {
                    foreach (['INSERT', 'UPDATE'] as $operation) {
                        DB::unprepared("DROP TRIGGER IF EXISTS {$name}_{$operation}");
                    }
                } else {
                    DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
                }
            }
        }
    }
};
