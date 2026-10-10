<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LocalBookingTimeMigrationTest extends TestCase
{
    public function test_upgrade_removes_timezone_columns_without_changing_legacy_bookings(): void
    {
        $this->withLegacyDatabase(function (): void {
            $before = DB::table('bookings')->orderBy('id')->get()->map(function (object $booking): array {
                $attributes = (array) $booking;
                unset($attributes['scheduled_at_timezone']);

                return $attributes;
            })->all();

            $this->migration()->up();

            $this->assertFalse(Schema::hasColumn('bookings', 'scheduled_at_timezone'));
            $this->assertFalse(Schema::hasColumn('service_centers', 'timezone'));
            $this->assertSame($before, DB::table('bookings')->orderBy('id')->get()->map(fn (object $booking): array => (array) $booking)->all());
            $this->assertSame(2, DB::table('bookings')->whereNull('duration_minutes')->count());
        });
    }

    public function test_upgrade_stops_before_erasing_known_utc_provenance(): void
    {
        $this->withLegacyDatabase(function (): void {
            DB::table('bookings')->where('status', 'pending')->update(['scheduled_at_timezone' => 'UTC']);
            $before = DB::table('bookings')->orderBy('id')->get()->toJson();

            try {
                $this->migration()->up();
                $this->fail('UTC bookings must be reviewed before migration.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('existing UTC bookings', $exception->getMessage());
            }

            $this->assertTrue(Schema::hasColumn('service_centers', 'timezone'));
            $this->assertSame($before, DB::table('bookings')->orderBy('id')->get()->toJson());
        });
    }

    public function test_upgrade_stops_for_centers_outside_egypt(): void
    {
        $this->withLegacyDatabase(function (): void {
            DB::table('service_centers')->update(['timezone' => 'Europe/London']);

            try {
                $this->migration()->up();
                $this->fail('Non-Egyptian schedules must be reviewed before migration.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('centers outside Egypt', $exception->getMessage());
            }

            $this->assertTrue(Schema::hasColumn('bookings', 'scheduled_at_timezone'));
            $this->assertSame('Europe/London', DB::table('service_centers')->value('timezone'));
        });
    }

    public function test_rollback_does_not_relabel_local_bookings_as_utc(): void
    {
        $this->withLegacyDatabase(function (): void {
            $migration = $this->migration();
            $migration->up();
            DB::table('bookings')->where('status', 'pending')->update(['scheduled_at' => '2026-10-12 09:00:00', 'duration_minutes' => 45]);

            $migration->down();

            $booking = DB::table('bookings')->where('status', 'pending')->first();
            $this->assertSame('2026-10-12 09:00:00', $booking->scheduled_at);
            $this->assertSame(45, $booking->duration_minutes);
            $this->assertNull($booking->scheduled_at_timezone);
            $migration->up();
            $this->assertFalse(Schema::hasColumn('bookings', 'scheduled_at_timezone'));
        });
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_10_130139_simplify_booking_times_to_egypt_local_time.php');
    }

    private function withLegacyDatabase(Closure $assertions): void
    {
        $originalConnection = config('database.default');
        config(['database.connections.local_time_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'local_time_migration_test']);
        DB::purge('local_time_migration_test');

        try {
            foreach (glob(database_path('migrations/*.php')) as $path) {
                if (basename($path) < '2026_10_10_130139') {
                    (require $path)->up();
                }
            }
            $user = DB::table('users')->insertGetId(['name' => 'Migration test', 'email' => 'local-time@example.test', 'password' => 'unused']);
            $governorate = DB::table('governorates')->insertGetId(['name' => 'Cairo', 'slug' => 'cairo']);
            $city = DB::table('cities')->insertGetId(['governorate_id' => $governorate, 'name' => 'Cairo', 'slug' => 'cairo']);
            $service = DB::table('services')->insertGetId(['name' => 'Service', 'slug' => 'service']);
            $center = DB::table('service_centers')->insertGetId(['city_id' => $city, 'owner_id' => $user, 'name' => 'Center', 'slug' => 'center', 'phone' => '01012345678', 'address' => 'Cairo']);
            foreach (['pending', 'completed'] as $status) {
                DB::table('bookings')->insert(['customer_id' => $user, 'service_id' => $service, 'service_center_id' => $center, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25 12:00:00', 'status' => $status]);
            }

            $assertions();
        } finally {
            DB::purge('local_time_migration_test');
            config(['database.default' => $originalConnection]);
        }
    }
}
