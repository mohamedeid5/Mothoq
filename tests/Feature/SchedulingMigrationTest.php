<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchedulingMigrationTest extends TestCase
{
    public function test_upgrade_preserves_legacy_bookings_and_leaves_durations_and_time_provenance_unknown(): void
    {
        $originalConnection = config('database.default');
        config(['database.connections.scheduling_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'scheduling_migration_test']);
        DB::purge('scheduling_migration_test');

        try {
            $oldMigrations = array_filter(glob(database_path('migrations/*.php')), fn (string $path): bool => basename($path) < '2026_10_09_165800');
            foreach ($oldMigrations as $path) {
                (require $path)->up();
            }
            $user = DB::table('users')->insertGetId(['name' => 'Migration test', 'email' => 'migration@example.test', 'password' => 'unused']);
            $governorate = DB::table('governorates')->insertGetId(['name' => 'Cairo', 'slug' => 'cairo']);
            $city = DB::table('cities')->insertGetId(['governorate_id' => $governorate, 'name' => 'Cairo', 'slug' => 'cairo']);
            $service = DB::table('services')->insertGetId(['name' => 'Service', 'slug' => 'service']);
            $center = DB::table('service_centers')->insertGetId(['city_id' => $city, 'owner_id' => $user, 'name' => 'Center', 'slug' => 'center', 'phone' => '01012345678', 'address' => 'Cairo']);
            DB::table('service_service_center')->insert(['service_id' => $service, 'service_center_id' => $center]);
            foreach (['pending', 'accepted', 'completed', 'cancelled'] as $status) {
                DB::table('bookings')->insert(['customer_id' => $user, 'service_id' => $service, 'service_center_id' => $center, 'customer_phone' => '01012345678', 'scheduled_at' => '2026-09-25 12:00:00', 'status' => $status]);
            }
            $before = DB::table('bookings')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
            Artisan::call('scheduling:audit');
            $audit = Artisan::output();
            $this->assertStringContainsString('assignments_without_duration', $audit);
            $this->assertStringContainsString('active_bookings_without_duration', $audit);

            foreach (glob(database_path('migrations/2026_10_09_16580*.php')) as $path) {
                (require $path)->up();
            }

            $after = DB::table('bookings')->orderBy('id')->get()->map(function (object $row): array {
                $this->assertNull($row->duration_minutes);
                $this->assertNull($row->scheduled_at_timezone);
                $attributes = (array) $row;
                unset($attributes['duration_minutes'], $attributes['scheduled_at_timezone']);

                return $attributes;
            })->all();
            $this->assertSame($before, $after);
            $this->assertNull(DB::table('service_service_center')->value('duration_minutes'));
            $this->assertSame('Africa/Cairo', DB::table('service_centers')->value('timezone'));
            Artisan::call('scheduling:audit');
            $this->assertStringContainsString('Read only: no durations, times, or statuses were changed.', Artisan::output());

            foreach (array_reverse(glob(database_path('migrations/2026_10_09_16580*.php'))) as $path) {
                (require $path)->down();
            }
            $this->assertSame($before, DB::table('bookings')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all());
        } finally {
            DB::purge('scheduling_migration_test');
            config(['database.default' => $originalConnection]);
        }
    }
}
